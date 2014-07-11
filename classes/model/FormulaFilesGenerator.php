<?php

/*
 * Copyright (C) 2014 Nikolai Neff <admin@flatplane.de>.
 *
 * This file is part of Flatplane.
 *
 * Flatplane is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Flatplane is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Flatplane.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace de\flatplane\model;

use de\flatplane\controller\Flatplane;
use de\flatplane\interfaces\documentElements\FormulaInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * todo: doc, error-checking, update paths?
 * Description of GenerateFormulas
 * @author Nikolai Neff <admin@flatplane.de>
 */
class FormulaFilesGenerator
{
    protected $formulas = [];
    protected $process;
    protected $masterCurlHandle;
    protected $curlHandles;
    protected $svgTexPort = 16000;

    public function __construct(array $content, $cleanDir = false)
    {
        if ($cleanDir) {
            $this->cleanUp();
        }

        $totalNum = 0;
        $renderNum = 0;
        foreach ($content as $formula) {
            if (!($formula instanceof FormulaInterface)) {
                throw new RuntimeException(
                    'Invalid object supplied to GenerateFormulas: '.
                    gettype($formula)
                );
            }
            $formula->applyStyles();
            if ($formula->getUseCache() == false
                || $this->isCached($formula) == false
            ) {
                $font = $formula->getFormulaFont();
                $this->formulas[$font][] = $formula;
                $renderNum ++;
            }
            $totalNum ++;
        }
        Flatplane::log(
            $totalNum.' Formulas total, '.$renderNum.' need rendering'
        );
    }

    public static function cleanUp()
    {
        $dir = Flatplane::getCacheDir().DIRECTORY_SEPARATOR.'formulas';
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.svg');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function generateFiles()
    {
        if (!empty($this->formulas)) {
            foreach ($this->formulas as $font => $formula) {
                $this->setSVGTEXFont($font);
                $this->startSVGTEX($font);
                $this->curlRequest($font);
                $this->stopSVGTEX();
            }
        } else {
            Flatplane::log('No formulas to (re)render; skipping');
        }
    }

    protected function isCached(FormulaInterface $formula)
    {
        $filename = $formula->getPath();
        if (file_exists($filename) && is_readable($filename)) {
            return true;
        } else {
            return false;
        }
    }

    protected function setSVGTEXFont($font)
    {
        //todo: validate font?
        $content = "//AUTOGENERATED! DO NOT EDIT \n";
        $content .= 'MathJax.Hub.Config({SVG:{font:"'.$font.'"}});'."\n";
        $content .= 'MathJax.Ajax.loadComplete("[MathJax]/config/local/font.js");';

        $filename = 'vendor'.DIRECTORY_SEPARATOR.'mathjax'.DIRECTORY_SEPARATOR
                    .'mathjax'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR
                    .'local'.DIRECTORY_SEPARATOR.'font.js';

        if (!is_writable(dirname($filename))) {
            throw new \RuntimeException('SVGTEX font.js file is not writable');
        }
        file_put_contents($filename, $content);
    }

    /**
     * fixme: check for nonexistant phantomjs!
     * todo: error checking, outputhandling/logging?
     * todo: doc
     */
    protected function startSVGTEX($font)
    {
        //todo: path, os, port!
        $phantomPath = Flatplane::getPhantomJsPath();
        $svgTexPath = 'vendor'.DIRECTORY_SEPARATOR.'flatplane'
                    .DIRECTORY_SEPARATOR.'svgtex'.DIRECTORY_SEPARATOR.'main.js';
        $port = $this->getSvgTexPort();
        $this->process = new Process(
            $phantomPath.' '.$svgTexPath.' -p '.$port
        );
        $this->process->setTimeout(20);
        $this->process->setIdleTimeout(20);
        $this->process->start();


        Flatplane::log(
            "Starting SVGTeX for font $font on port $port, please wait."
        );

        while ($this->process->isRunning()) {
            $this->process->checkTimeout();
            $out = $this->process->getOutput();
            if (!empty($out)) {
                Flatplane::log("\t SVGTex: ".$out);
            }
            if (strpos($out, 'Server started')!==false) {
                //exit loop
                break;
            } elseif (strpos(strtolower($out), 'error')!==false) {
                Flatplane::log("\t SVGTex: Error:", $this->process->getOutput());
                $this->process->clearOutput();
                $this->process->stop();
                die('SVGTEX-INIT-Failed');
            } else {
                $this->process->clearOutput();
            }
            //wait 1/8 sec: this value is exactly representable as float
            sleep(0.125);
        }
        Flatplane::log("SVGTeX is running");
    }

    protected function curlRequest($font)
    {
        //todo: check server response instead of process
        if (!$this->process->isRunning()) {
            throw new RuntimeException('The SVGTeX process is not runnig');
        }

        $this->curlInit($font);
        $this->curlExec();
        $this->curlRead($font);
    }

    protected function curlInit($font)
    {
        $this->masterCurlHandle = curl_multi_init();
        $this->curlHandles = array();
        foreach ($this->formulas[$font] as $key => $formula) {
            $format = strtolower($formula->getCodeFormat());
            $url = 'http://localhost:'.$this->getSvgTexPort().'/';
            $request = 'type='.$format.'&q='.urlencode($formula->getCode());

            $this->curlHandles[$key] = curl_init();
            curl_setopt($this->curlHandles[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curlHandles[$key], CURLOPT_URL, $url);
            curl_setopt($this->curlHandles[$key], CURLOPT_HEADER, false);
            curl_setopt($this->curlHandles[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curlHandles[$key], CURLOPT_POST, true);
            curl_setopt($this->curlHandles[$key], CURLOPT_POSTFIELDS, $request);
            curl_multi_add_handle(
                $this->masterCurlHandle,
                $this->curlHandles[$key]
            );
        }
    }

    protected function curlExec()
    {
        do {
            curl_multi_exec($this->masterCurlHandle, $running);
        } while ($running > 0);
    }

    protected function curlRead($font)
    {
        $numGenerated = 0;
        foreach ($this->formulas[$font] as $key => $formula) {
            $result = curl_multi_getcontent($this->curlHandles[$key]);

            $this->validateResult($result);

            $dir = Flatplane::getCacheDir().DIRECTORY_SEPARATOR.'formulas';
            if (!is_dir($dir)) {
                //todo: test permissions
                mkdir($dir);
            }
            if (!is_writable($dir)) {
                trigger_error(
                    'Formula cache directory is not writable',
                    E_USER_WARNING
                );
            }
            $filename = $dir.DIRECTORY_SEPARATOR.$formula->getHash().'.svg';
            Flatplane::log("\t SVGTex: writing ".$filename);
            file_put_contents($filename, $result);
            $formula->setPath($filename);
            $numGenerated ++;
        }
        Flatplane::log($numGenerated.' Formulas generated');
    }

    protected function stopSVGTEX()
    {
        $this->process->stop();
        Flatplane::log("SVGTeX stopped");
    }

    protected function validateResult($result)
    {
        if (empty($result) || strpos($result, '<svg') !== 0) {
            trigger_error(
                'The SVGTeX result is not a valid SVG file. Error: '.$result,
                E_USER_WARNING
            );
        }
    }
    public function getSvgTexPort()
    {
        return $this->svgTexPort;
    }

    public function setSvgTexPort($svgTexPort)
    {
        $this->svgTexPort = $svgTexPort;
    }
}

<?php

/*
 * Copyright (C) 2014 Nikolai Neff <admin@flatplane.de>.
 *
 * This file is part of Flatplane.
 *
 * Flatplane is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or(at your option) any later version.
 *
 * Flatplane is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Flatplane.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace de\flatplane\documentContents;

use de\flatplane\interfaces\documentElements\FormulaInterface;

/**
 * Description of formula
 *
 * @author Nikolai Neff <admin@flatplane.de>
 */
class Formula extends AbstractDocumentContentElement implements FormulaInterface
{
    protected $type='formula';
    protected $allowSubContent = ['formula'];
    protected $isSplitable = false;
    protected $title='Formula';

    protected $code;
    protected $font = 'TeX';
    protected $codeFormat = 'TeX';
    protected $availableFonts = ['TeX', 'STIX-Web', 'Asana-Math', 'Neo-Euler',
                                'Gyre-Pagella', 'Gyre-Termes', 'Latin-Modern'];
    protected $availableCodeFormats = ['TeX','MML'];
    protected $formulaStyle = 'display'; //options: display, inline

    protected $useCache = true;
    protected $path;

    public function getCode()
    {
        return $this->code;
    }

    public function getCodeFormat()
    {
        return $this->codeFormat;
    }

    public function getAvailableFonts()
    {
        return $this->availableFonts;
    }

    public function getAvailableCodeFormats()
    {
        return $this->availableCodeFormats;
    }

    public function getSize()
    {
        if (!empty($this->getPath())) {
            $size = $this->getSizeFromFile();
        } else {
            trigger_error('formula size requested before render', E_USER_WARNING);
            $size = ['height' => 0, 'width' => 0, 'numPages' => 0];
        }
        return $size;
    }

    protected function getSizeFromFile()
    {
        if (!is_readable($this->getPath())) {
            trigger_error('formula svg path not readable', E_USER_WARNING);
        }
        //dimensionen aus SVG extrahieren
        $xml = simplexml_load_file($this->getPath());

        var_dump($attrib = explode(';', $xml->attributes()->style[0]));
        echo $width = ltrim($attrib[0], 'width: '), PHP_EOL;
        echo $height =ltrim($attrib[1], ' height: '), PHP_EOL;

        echo $this->toRoot()->getPdf()->getHTMLUnitToUnits($width). PHP_EOL;
        echo $this->toRoot()->getPdf()->getHTMLUnitToUnits($width). PHP_EOL;
    }

    public function getHash()
    {
        return sha1($this->getCode());
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    protected function setCode($code)
    {
        $this->code = $code;
    }

    protected function setFont($font)
    {
        if (!in_array($font, $this->availableFonts, true)) {
            trigger_error(
                "Font $font not available, defaulting to TeX",
                E_USER_NOTICE
            );
            $font = 'TeX';
        }
        $this->font = $font;
    }

    protected function setCodeFormat($codeFormat)
    {
        if (!in_array($codeFormat, $this->availableCodeFormats, true)) {
            trigger_error(
                "Format $codeFormat not available, defaulting to TeX",
                E_USER_NOTICE
            );
            $codeFormat = 'TeX';
        }
        $this->codeFormat = $codeFormat;
    }

    public function getUseCache()
    {
        return $this->useCache;
    }

    protected function setUseCache($useCache)
    {
        $this->useCache = (bool) $useCache;
    }

    public function getFormulaStyle()
    {
        return $this->formulaStyle;
    }

    protected function setFormulaStyle($formulaStyle)
    {
        $this->formulaStyle = $formulaStyle;
    }

    public function applyStyles()
    {
        if ($this->getCodeFormat() == 'TeX') {
            switch ($this->getFormulaStyle()) {
                case 'inline':
                    if (strpos($this->code, '\displaystyle') === 0) {
                        $this->code = substr($this->code, 12);
                    }
                    break;
                case 'display':
                    if (strpos($this->code, '\displaystyle') !== 0) {
                        $this->code = '\displaystyle{'.$this->code.'}';
                    }
                    break;
                default:
                    break;
            }
        }
    }
}

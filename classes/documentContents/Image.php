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

/**
 * Description of Image
 *
 * @author Nikolai Neff <admin@flatplane.de>
 */
class Image extends AbstractDocumentContentElement
{
    protected $type = 'image';
    protected $allowSubContent = ['image'];

    protected $title = 'Image';
    protected $titlePosition = 'top';

    protected $path;
    protected $imageType;

    protected $caption;
    protected $captionPosition = 'bottom';

    protected $rotation = 0;
    protected $resolution = '72'; //dpi
    protected $width;
    protected $height;
    protected $keepAspectRatio = true;

    protected $placement = 'here'; //other options: (?) section[level]/top/bot/page

    public function getSize()
    {
        $filename = $this->getPath();
        if (!is_readable($filename)) {
            throw new \RuntimeException('Image '.$filename.' is not readable');
        }
        if (empty($this->getImageType())) {
            $this->setImageType($this->estimateImageType());
        }
        return $this->getImageDimensions();
    }

    protected function estimateImageType()
    {
        //todo: use MIME-types and EXIF Data?
        $info = new SplFileInfo($this->getPath());
        return strtolower($info->getExtensions());
    }

    protected function getImageDimensions()
    {
        if (empty($this->getWidth()) && empty($this->getHeight())) {
            $dimensions = $this->getImageDimensionsFromFile();
        } else {
            if (is_numeric($this->getWidth()) && is_numeric($this->getHeight())) {
                $dimensions['width'] = $this->getWidth();
                $dimensions['height'] = $this->getHeight();
            } else {
                $dimensions = $this->parseDimensions();
            }
        }
        return $dimensions;
    }

    protected function parseDimensions()
    {
        $componets['width'] = explode('*', strtolower($this->getWidth()));
        $componets['height'] = explode('*', strtolower($this->getHeight()));

        if (!$this->checkComponets($componets)) {
            trigger_error(
                'Invalid Width/Height arguments supplied,'
                .' trying to read dimensions from file instead.',
                E_USER_WARNING
            );
            return $this->getImageDimensionsFromFile();
        }

        $doc = $this->toRoot();
        $pagewidth = $doc->getPageSize()['width'];
        $textwidth = $pagewidth - $doc->getPageMargins('left')
                                - $doc->getPageMargins('right');
        $pageheight = $doc->getPageSize()['height'];
        //todo: implement textheight:
        //$textheight : pageheight - margins[top/bot] - footnotes - header/footer

        foreach ($componets as $key => $direction) {
            if (count($direction) == 2) {
                $factor[$key] = $direction[0];
                $value[$key] = $direction[1];
            } else {
                $factor[$key] = 1;
                $value[$key] = $direction[0];
            }
            switch ($value[$key])
            {
                case 'pagewidth':
                    $value[$key] = $pagewidth;
                    break;
                case 'textwidth':
                    $value[$key] = $textwidth;
                    break;
                case 'pageheight':
                    $value[$key] = $pageheight;
                    break;
                default:
                    throw new \RuntimeException('invalid imagesize-component value');
            }
        }

        $width = $factor['width']*$value['width'];
        $height = $factor['height']*$value['height'];

        if ($this->getKeepAspectRatio()) {
            $origDim = $this->getImageDimensionsFromFile();
            $aspectRatio = $origDim['width']/$origDim['height'];
            $height = $width/$aspectRatio;
        }

        return ['width' => $width, 'height' => $height];
    }

    /**
     * todo: doc
     * @param array $components
     * @return boolean
     */
    protected function checkComponents(array $components)
    {
        //todo: set this as property?
        $allowedReferenceValues = ['textwidth','pagewidth','pageheight'];
        foreach ($components as $direction) {
            if (!is_array($direction)) {
                return false;
            }
            switch (count($direction))
            {
                case 1:
                    $direction[0] = strtolower($direction[0]);
                    if (!in_array($direction[0], $allowedReferenceValues, true)) {
                        return false;
                    }
                    break;
                case 2:
                    if (!is_numeric($direction[0])) {
                        return false;
                    }
                    $direction[1] = strtolower($direction[1]);
                    if (!in_array($direction[1], $allowedReferenceValues, true)) {
                        return false;
                    }
                    break;
                default:
                    return false;
            }
        }
        return true;
    }


    protected function getImageDimensionsFromFile()
    {
        if ($this->getImageType() == 'svg') {
            return $this->getSVGMeasurementsFromFile();
        } elseif ($this->getImageType() == 'eps') {
            return $this->getEPSMeasurementsFromFile();
        } else {
            return $this->getGDMeasurementsFromFile();
        }
    }

    protected function getSVGMeasurementsFromFile()
    {
        $svgSize = new SVGSize($this->getPath());
        $dimensions = $svgSize->getDimensions();

        $pdf = $this->toRoot()->getPdf();

        //convert given unit (usually "ex") to user-units
        $width = $pdf->getHTMLUnitToUnits(
            $dimensions['width'],
            $pdf->getFontSize(),
            $dimensions['wUnit']
        );
        $height = $pdf->getHTMLUnitToUnits(
            $dimensions['height'],
            $pdf->getFontSize(),
            $dimensions['hUnit']
        );

        return ['width' => $width,
                'height' => $height];
    }

    protected function getEPSMeasurementsFromFile()
    {
        //todo: implement me
    }

    protected function getGDMeasurementsFromFile()
    {
        $filename = $this->getPath();
        $imageInfos = getimagesize($filename);
        if ($imageInfos == false) {
            throw new \RuntimeException(
                'imagesize of '.$filename.' can\'t be determined; check if the '.
                'file is not corrupted and the image-format supported'
            );
        }

        return $this->convertImageSizeToUserUnits($imageInfos[0], $imageInfos[1]);
    }

    protected function convertImageSizeToUserUnits($width, $height)
    {
        $resolution = $this->estimateImageResolution();

        //todo here: adjust image scaling factor according to DPI

        $newWidth = $pdf->getHTMLUnitToUnits(
            $dimensions['width'],
            $pdf->getFontSize(),
            $dimensions['wUnit']
        );
        $newHeight = $pdf->getHTMLUnitToUnits(
            $dimensions['height'],
            $pdf->getFontSize(),
            $dimensions['hUnit']
        );

        return ['width' => $newWidth,
                'height' => $newHeight];
    }

    protected function estimateImageResolution()
    {

    }

    public function getTitlePosition()
    {
        return $this->titlePosition;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getImageType()
    {
        return $this->imageType;
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function getCaptionPosition()
    {
        return $this->captionPosition;
    }

    public function getRotation()
    {
        return $this->rotation;
    }

    public function getScale()
    {
        return $this->scale;
    }

    public function getResolution()
    {
        return $this->resolution;
    }

    public function getPlacement()
    {
        return $this->placement;
    }

    protected function setTitlePosition($titlePosition)
    {
        $this->titlePosition = $titlePosition;
    }

    protected function setPath($path)
    {
        $this->path = $path;
    }

    protected function setImageType($imageType)
    {
        $this->imageType = $imageType;
    }

    protected function setCaption($caption)
    {
        $this->caption = $caption;
    }

    protected function setCaptionPosition($captionPosition)
    {
        $this->captionPosition = $captionPosition;
    }

    protected function setRotation($rotation)
    {
        $this->rotation = $rotation;
    }

    protected function setScale($scale)
    {
        $this->scale = $scale;
    }

    protected function setResolution($resolution)
    {
        $this->resolution = $resolution;
    }

    protected function setPlacement($placement)
    {
        $this->placement = $placement;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    protected function setWidth($width)
    {
        $this->width = $width;
    }

    protected function setHeight($height)
    {
        $this->height = $height;
    }

    public function getKeepAspectRatio()
    {
        return $this->keepAspectRatio;
    }

    protected function setKeepAspectRatio($keepAspectRatio)
    {
        $this->keepAspectRatio = $keepAspectRatio;
    }
}

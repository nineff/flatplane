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

use de\flatplane\interfaces\DocumentElementInterface;

//todo: formattierungsobjekte: newline, newpage, (h/v-space), clearpage?

//todo: complete documentation!
//todo: methoden sortieren

/**
 * Abstract class for all page elements like sections, text, images, formulas, ...
 * Provides basic common functionality.
 * @author Nikolai Neff <admin@flatplane.de>
 */
abstract class AbstractDocumentContentElement implements DocumentElementInterface
{
    //import functionality horizontally from traits to reduce code length
    use traits\ContentFunctions;
    use traits\NumberingFunctions;

    /**
     * @var DocumentElementInterface
     *  Contains a reference to the parent DocumentElement instance
     */
    protected $parent = null;

    /**
     * @var int
     *  type of the element
     */
    protected $type = 'PageElement';

    /**
     * @var bool
     *  defines if the element will recieve its own number
     */
    protected $enumerate = true;

    /**
     * @var string
     *  alternative (shorter) title to be used in lists for this element
     */
    protected $altTitle = '';

    /**
     * @var string
     *  title of the element
     */
    protected $title = '';

    /**
     * @var bool
     *  defines if the element will be displayed in autogenerated lists
     */
    protected $showInList = true;

    /**
     * @var mixed
     *  use a bool to completely allow/disallow subcontent for the element or
     *  define allowed types as array values: e.g. ['section', 'formula']
     *  todo: fix/use me?
     */
    protected $allowSubContent = true;

    /**
     * @var bool
     *  indicates if the element can be split across multiple pages
     */
    protected $isSplitable = false;

    /**
     * @var string
     *  name to identify the element in references
     *  //todo: unique?
     */
    protected $label = '';

    /**
     * todo: pagegroups?
     * @var int
     *  Number of the page this element gets printed on. If the element spans
     *  multiple pages, then this number references the first occurrence.
     */
    protected $page;

    /**
     * @var array
     *  defines the elements margins in user-units. Valid keys are:
     *  'top', 'bottom', 'left', 'right'. If any of those is undefined, the
     *  value of the key 'default' is used.
     */
    protected $margins = ['default' => 0];

    /**
     * @var array
     *  defines the elements paddings in user-units. Valid keys are:
     *  'top', 'bottom', 'left', 'right'. If any of those is undefined, the
     *  value of the key 'default' is used.
     */
    protected $paddings = ['default' => 0];

    /**
     * @var array
     *  defines the font-type/name/family to be used. Possible values are the
     *  name of a font-file or the family-identifier used by TCPDF::addFont()
     * @see TCPDF::addFont()
     */
    protected $fontType = ['default' => 'times'];

    /**
     * @var array
     *  possible values: Font size in pt
     */
    protected $fontSize = ['default' => 12];

    /**
     * @var array
     *  possible values: Font variations as strings:
     *  <ul>
     *   <li>(empty): normal</li>
     *   <li>U: underline</li>
     *   <li>D: strikethrough</li>
     *   <li>B: bold</li>
     *   <li>I: italic</li>
     *   <li>O: overline</li>
     *  </ul>
     * The variations can be combined (in any order): for example use 'BIU' to
     * create bold-italic-underlined text
     */
    protected $fontStyle = ['default' => ''];

    /**
     * Color used for text
     * @var array
     *  possible values:
     *  array containing 1 value (0-255) for grayscale
     *  array containing 3 values (0-255) for RGB colors or
     *  array contining 4 values (0-100) for CMYK colors
     */
    protected $fontColor = ['default' => [0,0,0]];

    /**
	 * @var array
     *  value (float): amount to increase or decrease the space between
     *  characters in a text (0 = default spacing)
     * @see TCPDF::setFontSpacing()
     */
    protected $fontSpacing = ['default' => 0];

    /**
	 * @var int percentage of stretching (default value: 100)
     * @see TCPDF::setFontStretching()
     */
    protected $fontStretching = ['default' => 100];

    /**
     * Color used for drawings (includes some font-styles like underline)
     * @var array
     *  possible values:
     *  array containing 1 value (0-255) for grayscale
     *  array containing 3 values (0-255) for RGB colors or
     *  array contining 4 values (0-100) for CMYK colors
     */
    protected $drawColor = ['default' => [0,0,0]];

    /**
     * Color used for fillings like cell-backgrounds
     * @var array
     *  possible values:
     *  array containing 1 value (0-255) for grayscale
     *  array containing 3 values (0-255) for RGB colors or
     *  array contining 4 values (0-100) for CMYK colors
     * @ignore todo: associative keys?
     */
    protected $fillColor = ['default' => [255,255,255]];

    protected $hyphenate = true;

    /**
     * This method is called on creating a new element.
     * @param array $config
     *  Array containing key=>value pairs wich overwrite the default properties.
     *  e.g.: $config = ['enumerate' => false] will disable numbering for the
     *  created instance
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * @param array $config
     *  Array containing key=>value pairs wich overwrite the default properties.
     *  e.g.: $config = ['enumerate' => false] will disable numbering for the
     *  created instance
     */
    public function setConfig(array $config)
    {
        $this->testNoParent();
        foreach ($config as $key => $setting) {
            $name = 'set'.ucfirst($key);
            if (method_exists($this, $name)) {
                $this->$name($setting);
            } else {
                trigger_error(
                    "$key is not a valid Configuration option, ignoring",
                    E_USER_NOTICE
                );
            }
        }
    }

    /**
     * todo: doc, maybe unneccesary?
     */
    public function __clone()
    {
        $this->parent = null;
    }

    public function __toString()
    {
        return (string) $this->getTitle();
    }

    /**
     * Sets the parent to an instance implementing DocumentElementInterface
     * @param DocumentElementInterface $parent
     */
    public function setParent(DocumentElementInterface $parent)
    {
        $this->parent = $parent;
    }

    public function applyStyles()
    {
        $pdf = $this->toRoot()->getPdf();
        $pdf->SetFont(
            $this->getFontType(),
            $this->getFontStyle(),
            $this->getFontSize()
        );
        $pdf->setColorArray('text', $this->getFontColor());
        $pdf->setColorArray('draw', $this->getDrawColor());
        $pdf->setColorArray('fill', $this->getFillColor());
        $pdf->setFontSpacing($this->getFontSpacing());
        $pdf->setFontStretching($this->getFontStretching());
    }

    /**
     * @return DocumentElementInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getPage()
    {
        if (empty($this->page)) {
            $this->page = str_repeat(
                $this->toRoot()->getUnresolvedReferenceMarker(),
                $this->toRoot()->getAssumedPageNumberWidth()
            );
        }
        return $this->page;
    }

    /**
     * todo: maybe protected, interface?
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @throws RuntimeException
     */
    protected function testNoParent()
    {
        if ($this->getParent() !== null) {
            throw new RuntimeException(
                "The configuration can't be changed after setting the parent"
            );
        }
    }

    /**
     * @param bool $enumerate
     */
    protected function setEnumerate($enumerate)
    {
        $this->enumerate = (bool) $enumerate;
    }

    /**
     * @param bool $showInList
     */
    protected function setShowInList($showInList)
    {
        $this->showInList = (bool) $showInList;
    }

    protected function setTitle($title)
    {
        $this->title = $title;
    }

    protected function setMargins(array $margins)
    {
        $this->margins = array_merge($this->margins, $margins);
    }

    protected function setPaddings(array $paddings)
    {
        $this->paddings = array_merge($this->paddings, $paddings);
    }

    protected function setFontType(array $fontType)
    {
        $this->fontType = array_merge($this->fontType, $fontType);
    }

    protected function setFontSize(array $fontSize)
    {
        $this->fontSize = array_merge($this->fontSize, $fontSize);
    }

    protected function setFontStyle(array $fontStyle)
    {
        $this->fontStyle = array_merge($this->fontStyle, $fontStyle);
    }

    protected function setFontColor(array $fontColor)
    {
        $this->fontColor = array_merge($this->fontColor, $fontColor);
    }

    protected function setDrawColor(array $drawColor)
    {
        $this->drawColor = array_merge($this->drawColor, $drawColor);
    }

    public function getFontSpacing($key = null)
    {
        if ($key !== null && isset($this->fontSpacing[$key])) {
            return $this->fontSpacing[$key];
        } else {
            return $this->fontSpacing['default'];
        }
    }

    public function getFontStretching($key = null)
    {
        if ($key !== null && isset($this->fontStretching[$key])) {
            return $this->fontStretching[$key];
        } else {
            return $this->fontStretching['default'];
        }
    }

    protected function setFontSpacing(array $fontSpacing)
    {
        $this->fontSpacing = array_merge($this->fontSpacing, $fontSpacing);
    }

    protected function setFontStretching(array $fontStretching)
    {
        $this->fontStretching = array_merge($this->fontStretching, $fontStretching);
    }

    /**
     * @return bool
     */
    public function getEnumerate()
    {
        return $this->enumerate;
    }

    /**
     * @return bool
     */
    public function getShowInList()
    {
        return $this->showInList;
    }

    /**
     * @return mixed
     */
    public function getAllowSubContent()
    {
        return $this->allowSubContent;
    }

    /**
     * @return bool
     */
    public function getIsSplitable()
    {
        return $this->isSplitable;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * set a label as identifier for references
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }


    protected function setFillColor(array $fillColor)
    {
        $this->fillColor = $fillColor;
    }

    public function getMargins($key = null)
    {
        if ($key !== null && isset($this->margins[$key])) {
            return $this->margins[$key];
        } else {
            return $this->margins['default'];
        }
    }

    public function getPaddings($key = null)
    {
        if ($key !== null && isset($this->paddings[$key])) {
            return $this->paddings[$key];
        } else {
            return $this->paddings['default'];
        }
    }

    public function getFontType($key = null)
    {
        if ($key !== null && isset($this->fontType[$key])) {
            return $this->fontType[$key];
        } else {
            return $this->fontType['default'];
        }
    }

    public function getFontSize($key = null)
    {
        if ($key !== null && isset($this->fontSize[$key])) {
            return $this->fontSize[$key];
        } else {
            return $this->fontSize['default'];
        }
    }

    public function getFontStyle($key = null)
    {
        if ($key !== null && isset($this->fontStyle[$key])) {
            return $this->fontStyle[$key];
        } else {
            return $this->fontStyle['default'];
        }
    }

    public function getFontColor($key = null)
    {
        if ($key !== null && isset($this->fontColor[$key])) {
            return $this->fontColor[$key];
        } else {
            return $this->fontColor['default'];
        }
    }

    public function getDrawColor($key = null)
    {
        if ($key !== null && isset($this->drawColor[$key])) {
            return $this->drawColor[$key];
        } else {
            return $this->drawColor['default'];
        }
    }

    public function getFillColor($key = null)
    {
        if ($key !== null && isset($this->fillColor[$key])) {
            return $this->fillColor[$key];
        } else {
            return $this->fillColor['default'];
        }
    }

    protected function setAltTitle($altTitle)
    {
        $this->altTitle = $altTitle;
    }

    public function getAltTitle()
    {
        if (empty($this->altTitle)) {
            return $this->getTitle();
        } else {
            return $this->altTitle;
        }
    }

    /**
     * todo: doc
     * @return array
     */
    protected function getPageMeasurements()
    {
        //doto: footnotes
        $doc = $this->toRoot();
        $pagewidth = $doc->getPageSize()['width'];
        $textwidth = $pagewidth - $doc->getPageMargins('left')
                                - $doc->getPageMargins('right');
        $pageheight = $doc->getPageSize()['height'];
        $textheight = $pageheight - $doc->getPageMargins('top')
                                  - $doc->getPageMargins('bottom');

        return ['pagewidth' => $pagewidth,
                'textwidth' => $textwidth,
                'pageheight' => $pageheight,
                'textheight' => $textheight];
    }

    public function getHyphenate()
    {
        return $this->hyphenate;
    }

    protected function setHyphenate($hyphenate)
    {
        $this->hyphenate = $hyphenate;
    }

    public function hyphenateTitle()
    {
        if ($this->getHyphenate()) {
            $this->setTitle($this->toRoot()->hypenateText($this->getTitle()));
            $this->setAltTitle($this->toRoot()->hypenateText($this->getAltTitle()));
        }
    }
}

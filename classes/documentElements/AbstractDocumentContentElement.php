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

namespace de\flatplane\documentElements;

use de\flatplane\interfaces\DocumentElementInterface;
use RuntimeException;

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
     * @var string
     *  Number of the page this element gets printed on. If the element spans
     *  multiple pages, then this number references the first occurrence.
     */
    protected $page;

    protected $linearPage;

    /**
     * @var array
     *  defines the elements margins in user-units. Standard keys are:
     *  'top', 'bottom', 'left', 'right'. Subclasses might define their own keys.
     *  If any of those are undefined, the value of the key 'default' is used.
     */
    protected $margins = ['default' => 0];

//    /**
//     * @var array
//     *  defines the elements paddings in user-units. Standard keys are:
//     *  'top', 'bottom', 'left', 'right'. Subclasses might define their own keys.
//     *  If any of those are undefined, the value of the key 'default' is used.
//     */
//    protected $paddings = ['default' => 0];

    /**
     * @var array
     *  defines the paddings of text-content in cells in user units
     *  Standard keys are:
     *  'top', 'bottom', 'left', 'right'. Subclasses might define their own keys.
     *  If any of those are undefined, the value of the key 'default' is used.
     */
    protected $cellPaddings = ['default' => 0];

    /**
     * @var array
     *  defines the margins of text-content in cells in user units
     *  Standard keys are:
     *  'top', 'bottom', 'left', 'right'. Subclasses might define their own keys.
     *  If any of those are undefined, the value of the key 'default' is used.
     */
    protected $cellMargins = ['default' => 0];

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
     * @var float
     *  line-pitch scaling factor. Adjust thois to increase or decrease the
     *  vertical distance between lines relative to the font-size
     * @see TCPDF::setCellHeightRatio()
     */
    protected $linePitch = 1.25;

    //todo: use this?
//    protected $keepMarginsAfterPageBreak = ['default' => false,
//                                            'top' => false,
//                                            'bottom' =>false];

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

    public function applyStyles($key = null)
    {
        $pdf = $this->toRoot()->getPDF();
        $pdf->SetFont(
            $this->getFontType($key),
            $this->getFontStyle($key),
            $this->getFontSize($key)
        );
        $pdf->setColorArray('text', $this->getFontColor($key));
        $pdf->setColorArray('draw', $this->getDrawColor($key));
        $pdf->setColorArray('fill', $this->getFillColor($key));
        $pdf->setFontSpacing($this->getFontSpacing($key));
        $pdf->setFontStretching($this->getFontStretching($key));

        $pdf->setCellMargins(
            $this->getCellMargins('left'),
            $this->getCellMargins('top'),
            $this->getCellMargins('right'),
            $this->getCellMargins('bottom')
        );

        $pdf->setCellPaddings(
            $this->getCellPaddings('left'),
            $this->getCellPaddings('top'),
            $this->getCellPaddings('right'),
            $this->getCellPaddings('bottom')
        );

        $pdf->setCellHeightRatio($this->getLinePitch());
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

    /**
     * @return mixed
     */
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
     * @param mixed $page
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
        if ($this->getParent() !== null) {
            trigger_error(
                'setEnumerate() should not be called after adding the element '
                .'as content. Doing so might lead to erratic behavior regarding'
                .'element numbering. Please use the $settings array in the '
                .'constructor instead',
                E_USER_NOTICE
            ) ;
        }
        $this->enumerate = (bool) $enumerate;
    }

    /**
     * @param bool $showInList
     */
    public function setShowInList($showInList)
    {
        $this->showInList = (bool) $showInList;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     *
     * @param array $margins
     *  keys: 'top', 'bottom', 'left', 'right'
     *  values: (numeric) margin amount (user units)
     */
    public function setMargins(array $margins)
    {
        $this->margins = array_merge($this->margins, $margins);
    }

//    protected function setPaddings(array $paddings)
//    {
//        $this->paddings = array_merge($this->paddings, $paddings);
//    }

    public function setFontType(array $fontType)
    {
        $this->fontType = array_merge($this->fontType, $fontType);
    }

    public function setFontSize(array $fontSize)
    {
        $this->fontSize = array_merge($this->fontSize, $fontSize);
    }

    public function setFontStyle(array $fontStyle)
    {
        $this->fontStyle = array_merge($this->fontStyle, $fontStyle);
    }

    public function setFontColor(array $fontColor)
    {
        $this->fontColor = array_merge($this->fontColor, $fontColor);
    }

    public function setDrawColor(array $drawColor)
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

    public function setFontSpacing(array $fontSpacing)
    {
        $this->fontSpacing = array_merge($this->fontSpacing, $fontSpacing);
    }

    public function setFontStretching(array $fontStretching)
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
        if (!in_array($this, $this->toRoot()->getLabels())) {
            $this->toRoot()->addLabel($this);
        }
    }


    public function setFillColor(array $fillColor)
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

    public function setAltTitle($altTitle)
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
     * todo: change back to protected or add to interface
     * @return array
     *  Keys: pageWidth, textWidth, pageHeight, textHeight
     */
    public function getPageMeasurements()
    {
        //todo: footnotes
        $doc = $this->toRoot();
        $pageWidth = $doc->getPageSize()['width'];
        $textWidth = $pageWidth - $doc->getPageMargins('left')
                                - $doc->getPageMargins('right');
        $pageHeight = $doc->getPageSize()['height'];
        $textHeight = $pageHeight - $doc->getPageMargins('top')
                                  - $doc->getPageMargins('bottom');

        return ['pageWidth' => $pageWidth,
                'textWidth' => $textWidth,
                'pageHeight' => $pageHeight,
                'textHeight' => $textHeight];
    }

    /**
     *
     * @return bool
     */
    public function getHyphenate()
    {
        return $this->hyphenate;
    }

    /**
     *
     * @param bool $hyphenate
     */
    public function setHyphenate($hyphenate)
    {
        $this->hyphenate = $hyphenate;
    }

    /**
     * todo: doc
     */
    public function hyphenateTitle()
    {
        if ($this->getHyphenate()) {
            if ($this->getAltTitle() !== $this->getTitle()) {
                $this->setAltTitle(
                    $this->toRoot()->hypenateText($this->getAltTitle())
                );
            }
            $this->setTitle($this->toRoot()->hypenateText($this->getTitle()));
        }
    }

    /**
     * @todo: rename&getall
     * @param string $key
     * @return float
     */
    public function getCellMargins($key = null)
    {
        if ($key !== null && isset($this->cellMargins[$key])) {
            return $this->cellMargins[$key];
        } else {
            return $this->cellMargins['default'];
        }
    }

    /**
     * @todo: s.o.
     * @param string $key
     * @return float
     */
    public function getCellPaddings($key = null)
    {
        if ($key !== null && isset($this->cellPaddings[$key])) {
            return $this->cellPaddings[$key];
        } else {
            return $this->cellPaddings['default'];
        }
    }

    /**
     *
     * @param array $cellMargins
     *  keys: 'top', 'bottom', 'left', 'right'
     *  values: (numeric) margin amount (user units)
     */
    public function setCellMargins(array $cellMargins)
    {
        $this->cellMargins = array_merge($this->cellMargins, $cellMargins);
    }

    /**
     *
     * @param array $cellPaddings
     *  keys: 'top', 'bottom', 'left', 'right'
     *  values: (numeric) margin amount (user units)
     */
    public function setCellPaddings(array $cellPaddings)
    {
        $this->cellPaddings = array_merge($this->cellPaddings, $cellPaddings);
    }

    /**
     *
     * @return float
     */
    public function getLinePitch()
    {
        return $this->linePitch;
    }

    /**
     *
     * @param float $linePitch
     */
    public function setLinePitch($linePitch)
    {
        $this->linePitch = $linePitch;
    }

    /**
     * @return array
     */
    public function getSize($startYposition = null)
    {
        //todo: return width?
        $pdf = $this->toRoot()->getPDF();
        $pdf->startMeasurement($startYposition);
        $this->generateOutput();
        return $pdf->endMeasurement();
    }

    public function getLinearPage()
    {
        return $this->linearPage;
    }

    public function setLinearPage($linearPage)
    {
        $this->linearPage = $linearPage;
    }

    /**
     * todo: doc
     * @param string $source
     * @param string $extras
     * @return string
     */
    public function cite($source, $extras = '')
    {
        //todo: remove cite prefix/postfix, own style for Cite!
        $sourceList = $this->toRoot()->getSources();
        if (array_key_exists($source, $sourceList)) {
            $citeStyle = $this->toRoot()->getCitationStyle();
            //$cite = $citeStyle['prefix'];
            $cite = '';
            $cite .= $sourceList[$source]->getFormattedNumbers();
            $sourceList[$source]->setParent($this);
            if (!empty($extras)) {
                $cite .= $citeStyle['separator'].' '.$extras;
            }
            //$cite .= $citeStyle['postfix'];
        } else {
            trigger_error(
                'Source "'.$source.'" for citation not found',
                E_USER_NOTICE
            );
            $cite = '[??]'; //todo: use assumption settings
        }
        return $cite;
    }
}

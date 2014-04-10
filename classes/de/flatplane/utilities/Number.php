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

namespace de\flatplane\utilities;

/**
 * Description of Number
 *
 * @author Nikolai Neff <admin@flatplane.de>
 */
class Number
{
    protected $value;

    public function __construct($value = 0)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string) $this->value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getFormatedValue($format, $numDecimals = 2, $dec_point = '.', $thousands_sep = '')
    {
        switch ($format) {
            case 'alpha':
                return $this->alpha('lower');
                break;

            case 'Alpha':
            case 'ALPHA':
                return $this->alpha('upper');
                break;

            case 'roman':
                return $this->roman('lower');
                break;

            case 'Roman':
            case 'ROMAN':
                return $this->roman('upper');
                break;

            case 'int':
            case 'Int':
            case 'INT':
                return number_format($this->value, $numDecimals, $dec_point, $thousands_sep);

            default:
                return $this->value;
                break;
        }
    }

    public function roman($case = 'upper')
    {
        if ($this->value == 0) {
            return 0;
        }

        $result = '';
        $tempNum = $this->value;
        if ($tempNum < 0) {
            $tempNum = abs($tempNum);
            $result .='-';
        }

        $chars = array('M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1);

        foreach ($chars as $roman => $value) {
            $numMatches = floor($tempNum / $value);

            $result .= str_repeat($roman, $numMatches);

            $tempNum %= $value;
        }

        if ($case == 'lower') {
            $result = strtolower($result);
        }
        return $result;
    }

    public function alpha($mode = 'upper')
    {
        if ($this->value == 0) {
            return 0;
        }

        $result = '';
        $tempNum = $this->value - 1;
        if ($tempNum < 0) {
            $tempNum = abs($tempNum) - 2; //2 twice off-by-one due to abs()
            $sign = '-';
        } else {
            $sign = '';
        }

        for ($i = 1; $tempNum >= 0; $i++) {
            $index = ($tempNum % pow(26, $i)) / pow(26, $i - 1);
            $result = chr(0x41 + $index) . $result;
            $tempNum -= pow(26, $i);
        }

        $result = $sign . $result;
        if ($mode == 'lower') {
            $result = strtolower($result);
        }

        return $result;
    }
}
<?php

/* 
 * Copyright (C) 2014 NoccyLabs.info
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Sdl\LiteralType;

/**
 * SDL Date: Year, month and day.
 */
class SdlDateTime extends LiteralType
{
    public static $match_pattern = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2}(\.[0-9]{1,3}([\-]{0,1}.*)?)?)?$/";
    
    private $value;
    
    public function setValue($value)
    {
        if (is_string($value)) 
        {
            $date = strtotime($value);
        }
        
        $this->value = $date;
    }
    
    public function getValue()
    {
        return $this->value;
        
    }
    
    public function setSdlLiteral($string)
    {
        $match = null;
        preg_match_all(self::RE_DATETIME, $value, $match);
        list($year, $month, $day) = [(int) $match[1][0], (int) $match[2][0], (int) $match[3][0]];
        list($hour, $minute, $second) = [(int) $match[4][0], (int) $match[5][0], $match[6][0]];
        list($micro, $tz) = [(float) $match[7][0], (string) $match[8][0]];
        if ($second)
            $second = (int) substr($second, 1);
        else
            $second = 0;
        if ($tz && (!defined("SDL_IGNORE_TIMEZONE")))
        {
            throw new SdlParserException("Timezones for dates are not implemented. Define SDL_IGNORE_TIMEZONE to disable this exception.", SdlParserException::ERR_NOT_IMPLEMENTED);
        }
        // TODO: Implement timezones
        $ts = mktime($hour, $minute, $second, $month, $day, $year); //
        $ts+= $micro;
        //echo "{$value} =>\n Y: {$year}\n M: {$month}\n D: {$day}\n H: {$hour}\n M: {$minute}\n S: {$second}\n µ: {$micro}\n TZ: {$tz}\n ==> {$ts}\n";
        $this->value = $ts;
    }
    
    public function getSdlLiteral()
    {
        return date("Y/m/d h:i:s",$this->value);
    }
    
}
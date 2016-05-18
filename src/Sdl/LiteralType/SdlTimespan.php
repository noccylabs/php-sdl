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
class SdlTimespan extends LiteralType
{
    public static $match_pattern = "/^([0-9]{0,5}d\:){0,1}([0-9]{0,5}:){0,1}([0-9]{1,2}):([0-9]{1,2})(\.[0-9]{1,3})?$/";
    
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
        preg_match_all(self::$match_pattern, $string, $match);
        list($days, $hours, $minutes, $seconds, $micro) = [(int) $match[1][0], (int) $match[2][0], (int) $match[3][0], (int) $match[4][0], (float) $match[5][0]];
        $time = ($seconds) + ($minutes * 60) + ($hours * 60 * 60) + ($days * 60 * 60 * 24);
        if ($micro)
            $time += $micro;
        // echo "{$value} =>\n H: {$hours}\n M: {$minutes}\n S: {$seconds}\n ==> {$time}\n";
        
        $this->value = $time;
    }
    
    public function getSdlLiteral()
    {
        return date("Y/m/d",$this->value);
    }
    
}
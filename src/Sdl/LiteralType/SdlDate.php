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
class SdlDate extends LiteralType
{
    public static $match_pattern = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/";
    
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
        $this->value = (int)$string;
    }
    
    public function getSdlLiteral()
    {
        return date("Y/m/d",$this->value);
    }
    
}
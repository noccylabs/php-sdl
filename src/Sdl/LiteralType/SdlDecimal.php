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
 * LiteralType for a SDL Decimal
 * 
 * 
 */
class SdlDecimal extends LiteralType
{

    static $match_pattern = "/^[\+\-]{0,1}[\.0-9]*bd$/i";
    
    private $value;
    
    public function setValue($value)
    {
        if (is_numeric($value))
        {
            $this->value = $value;
        }
        throw new \Sdl\Exception\TypeException("Invalid value for SdlDecimal");
    }
    
    public function getValue()
    {
        return $this->value;
        
    }
    
    public function setSdlLiteral($string)
    {
        if (preg_match(self::$match_pattern, $string))
        {
            $this->value = substr($string,0,-2);
        }
        throw new \Sdl\Exception\TypeException("Invalid literal for SdlDecimal");
    }
    
    public function getSdlLiteral()
    {
        return $this->value;
    }
    
}
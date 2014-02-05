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
 * LiteralType for an SDL Double
 */
class SdlDouble extends LiteralType
{
    public static $match_pattern = "/^[\+\-]{0,1}[0-9]*\.[0-9]*d?$/i";
    
    private $value;
    
    public function setValue($value)
    {
        $this->value = (double)$value;
    }
    
    public function getValue()
    {
        return $this->value;
        
    }
    
    public function setSdlLiteral($string)
    {
        $this->value = (double)$string;
    }
    
    public function getSdlLiteral()
    {
        return $this->value;
    }
    
}
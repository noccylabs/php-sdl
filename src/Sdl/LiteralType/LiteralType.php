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

abstract class LiteralType
{

    public function __construct($value=null, $from_literal=false)
    {
        if ($from_literal)
        {
            $this->setSdlLiteral($value);
        }
        else
        {
            $this->setValue($value);
        }
    }

    public static function fromLiteral($value)
    {
        $type_class = get_called_class();
        $type_inst = new $type_class;
        $type_inst->setSdlLiteral($value);
        return $type_inst;
    }
    
    public function getType()
    {
        return preg_replace("|(.*)\\\\|","",get_called_class());
    }
    
    public function castTo(LiteralType $target)
    {
        $target->setValue($this->getValue());
        return $target;
    }
    
    abstract public function getValue();
    
    abstract public function setValue($value);
    
    abstract public function getSdlLiteral();
    
    abstract public function setSdlLiteral($string);
    
}

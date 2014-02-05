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
 * The LiteralType class is an abstract base class used to create the various
 * value types supported by the parser and encoder, such as SdlString, SdlInteger
 * or SdlBinary.
 * 
 * The class should be capable to read a value from specific SDL tokens, f.ex.:
 *   "Hello World"  ->  string: Hello World
 *   492            ->  int: 492
 * 
 * And also be able to format and return the respective literal tokens, f.ex.:
 *   bool:true      ->  "on"
 *   float:3.14     ->  3.14f
 * 
 * The getValue() and setValue() should cast the values as needed to match the
 * respective types, f.ex. assigning "no" to an SdlBool will be the same as
 * passing true (as ((bool)"no")) == true). Passing no to setSdlLiteral will
 * result in false (as it is not a string, no can't be quoted when passed).
 * 
 */
abstract class LiteralType
{

    /**
     * Constructor
     * 
     * @param mixed $value The value to assign
     * @param bool $from_literal If true the value is parsed as a literal
     */
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

    /**
     * Create a new instance from the specific derived literal type by passing
     * it a string literal.
     * 
     * @param type $value
     * @return \Sdl\LiteralType\type_class
     */
    public static function fromLiteral($value)
    {
        $type_class = get_called_class();
        $type_inst = new $type_class;
        $type_inst->setSdlLiteral($value);
        return $type_inst;
    }
    
    /**
     * Get the name of the type represented by the instance.
     * 
     * @return string The type name, f.ex. "SdlString"
     */
    public function getType()
    {
        return preg_replace("|(.*)\\\\|","",get_called_class());
    }
    
    /**
     * Cast a value type into another type.
     * 
     * @param \Sdl\LiteralType\LiteralType $target The instance to cast the value to.
     * @return \Sdl\LiteralType\LiteralType The cast value.
     */
    public function castTo(LiteralType $target)
    {
        $target->setValue($this->getValue());
        return $target;
    }
    
    /**
     * Get the value as its native PHP type.
     * 
     * @return mixed The value
     */
    abstract public function getValue();
    
    /**
     * Set the value from its native PHP type.
     * 
     * @param mixed $value The new value
     */
    abstract public function setValue($value);
    
    /**
     * Return the SDL literal as a string.
     * 
     * @return string The SDL literal as a string
     */
    abstract public function getSdlLiteral();
    
    /**
     * Set the value from its SDL literal.
     * 
     * @param string $string The SDL literal
     */
    abstract public function setSdlLiteral($string);
    
}

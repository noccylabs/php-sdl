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

namespace Sdl;

use Sdl\Exception\ParserException;

/**
 * 
 * 
 * 
 * 
 */
class SdlTag implements ISdlTag
{

    private $tag_name;
    private $tag_namespace;
    private $parent_tag;
    private $values = [];
    private $attributes = [];
    private $children = [];
    
    /**
     * Constructor
     * 
     * @param string $tag_name The name of the tag to create
     */
    public function __construct($tag_name=null)
    {
        if ($tag_name)
        {
            $this->setTagName ($tag_name);
        }
    }
    
    /**
     * Set the name of the tag, optionally including namespace. Throws a 
     * ParserException if the name is not valid.
     * 
     * @param type $name
     * @return \Sdl\SdlTag
     * @throws ParserException
     */
    public function setTagName($name)
    {
        // Check validity
        if ($this->isValidIdentifier($name))
        {
            // TODO: Extract namespace
            $this->tag_name = $name;
            return $this;
        }
        throw new ParserException(
            "Invalid identifier '{$name}'",
            ParserException::ERR_INVALID_IDENTIFIER
        );
    }
    
    /**
     * Get the tag name
     * 
     * @return string The name of the tag, including namespace if defined.
     */
    public function getTagName()
    {
        return $this->tag_name;
    }
    
    /**
     * Check if an attribute exists.
     * 
     * @param string $attribute The attribute name
     * @return bool True if the attribute exists
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributes);
    }
    
    /**
     * 
     * @param type $attribute
     * @return null
     */
    public function getAttribute($attribute)
    {
        if ($this->hasAttribute($attribute))
        {
            return $this->attributes[$attribute]->getValue();
        }
        return null;
    }
    
    /**
     * 
     * @param type $attribute
     * @param type $value
     * @return \Sdl\SdlTag
     */
    public function setAttribute($attribute,$value)
    {
        // Check validity
        if (!($value instanceof LiteralType\LiteralType))
        {
            $value = LiteralType\TypeFactory::createFromPhpValue($value);
        }
        $this->attributes[$attribute] = $value;
        return $this;
    }

    public function removeAttribute($attribute)
    {
        // TODO: Implement
    }
    
    /**
     * Set the first value (this[0]) to the specified value.
     * 
     * @param type $value
     */
    public function setValue($value)
    {
        if (!($value instanceof LiteralType\LiteralType))
        {
            $value = LiteralType\TypeFactory::createFromPhpValue($value);
        }
        $this->values[0] = $value;
        return $this;
    }
    
    /**
     * Set a value at a specific index
     * 
     * @param type $index
     * @param type $value
     * @return \Sdl\SdlTag
     */
    public function setValueAt($index,$value)
    {
        if (!($value instanceof LiteralType\LiteralType))
        {
            $value = LiteralType\TypeFactory::createFromPhpValue($value);
        }
        $this->values[$index] = $value;
        return $this;
    }
    
    public function setValuesFromArray(array $values)
    {
        $this->values = [];
        for($n = 0; $n < count($values); $n++)
        {
            $this->setValueAt($n, $values[$n]);
        }
        return $this;
        
    }
    
    public function getValue($index = 0)
    {
        if ($index < count($this->values))
        {
            return $this->values[$index]->getValue();
        }
    }
    
    public function getAllValues()
    {
        return array_map(function($obj){
            return $obj->getValue();
        }, $this->values);
    }
    
    public function hasChildren()
    {
        return (count($this->children)>0);
    }
    
    public function getChildren()
    {
        return $this->children;
    }
    
    public function addChild(ISdlTag $tag)
    {
        if (!($tag instanceof SdlComment))
        {
            $tag->setParent($this);
        }
        $this->children[] = $tag;
        return $this;
    }
    
    public static function createRoot()
    {
        $tag = new self;
        return $tag;
    }
    
    public function createChild($tagname)
    {
        $tag = new self;
        $tag->setTagName($tagname);
        $tag->setParent($this);
        $this->addChild($tag);
        return $tag;
    }
    
    public function createComment($text)
    {
        $comment = new SdlComment();
        $comment->setValue($text);
        $this->addChild($comment);
        return $this;
    }
    
    public function end()
    {
        return $this->getParent();
    }
    
    public function getParent()
    {
        return $this->parent_tag;
    }
    
    public function setParent(SdlTag $parent)
    {
        $this->parent_tag = $parent;
        return $this;
    }

    /**
     * Check if the specified identifier is valid per SDL 1.2
     * 
     * From the SDL language guide: An SDL identifier starts with a unicode
     * letter or underscore (_) followed by zero or more unicode letters,
     * numbers, underscores (_), dashes (-), periods (.) and dollar signs
     * ($).
     *
     * @param type $identifier
     * @return type
     */
    public function isValidIdentifier($identifier)
    {
        // Check if this identifier has a namespace, and if so check the parts
        // independently and return true only if both are valid.
        if (strpos($identifier,":")!==false)
        {
            list($namespace,$identifier) = explode(":",$identifier,2);
            return ($this->isValidIdentifier($namespace) &&
                    $this->isValidIdentifier($identifier));
        }
        // Check if the string is a valid identifier.
        return (preg_match("/^[_a-zA-Z]{1}[_\-\.\$a-zA-Z0-9]*/", $identifier));
    }
    
    /**
     * Encode the tag and its children into valid Sdl.
     * 
     * @return string
     */
    public function encodeTag()
    {
        $string = ($this->tag_namespace?":{$this->tag_namespace}":"")
                . ($this->tag_name)
                ;
        foreach($this->values as $value) {
            $string .= " {$value->getSdlLiteral()}";
        }
        foreach($this->attributes as $attribute=>$value) {
            $string .= " {$attribute}={$value->getSdlLiteral()}";
        }
        if (count($this->children)>0)
        {
            $string.= " {\n";
            foreach($this->children as $child)
            {
                $string.= "    ".str_replace("\n","\n    ",$child->encodeTag())."\n";
            }
            $string.= "}";
        }
        return $string;
    }
    
    public function encode()
    {
        if (count($this->children)>0)
        {
            $string = "";
            foreach($this->children as $child)
            {
                $string.= $child->encodeTag()."\n";
            }
        }
        return $string;
    }
    
}
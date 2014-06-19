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
 * SDL Tag Element
 * 
 * 
 * 
 */
class SdlTag implements ISdlElement
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
        if (SdlUtils::isValidIdentifier($name))
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
     * Get an attribute from the tag.
     * 
     * @param string $attribute
     * @return null|mixed The attribute
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
     * Create or update an attribute with a new value
     * 
     * @param string $attribute
     * @param mixed $value
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
    
    public function setAttributesFromArray(array $attributes)
    {
        foreach($attributes as $attr=>$value)
        {
            $this->setAttribute($attr,$value);
        }
    }

    /**
     * Remove an attribute from the tag
     * 
     * @param string $attribute
     */
    public function removeAttribute($attribute)
    {
        // TODO: Implement
    }
    
    /**
     * Set the first value (this[0]) to the specified value.
     * 
     * @param mixed $value
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
     * @param int $index
     * @param mixed $value
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
    
    /**
     * Assign the values of the tag from the array passed.
     * 
     * @param array $values The values to assign
     * @return \Sdl\SdlTag
     */
    public function setValuesFromArray(array $values)
    {
        $this->values = [];
        for($n = 0; $n < count($values); $n++)
        {
            $this->setValueAt($n, $values[$n]);
        }
        return $this;
        
    }
    
    /**
     * Get the first value, or the value at the specified index.
     * 
     * @param int $index 
     * @return mixed The value at the specified index (default: 0)
     */
    public function getValue($index = 0)
    {
        if ($index < count($this->values))
        {
            return $this->values[$index]->getValue();
        }
    }
    
    /**
     * Get all values from this tag.
     * 
     * @return array The values
     */
    public function getAllValues()
    {
        return array_map(function($obj){
            return $obj->getValue();
        }, $this->values);
    }
    
    /**
     * Check if the tag has children (including comments)
     * 
     * @return bool True if the tag has child tags
     */
    public function hasChildren()
    {
        // TODO: Skip the comment nodes?
        return (count($this->children)>0);
    }
    
    /**
     * Return an array of all the child tags.
     * 
     * @return array Array of child tags
     */
    public function getChildren()
    {
        return (array)$this->children;
    }
    
    /**
     * Get the child tags that match the given tag name. This only operates on
     * direct children and not recursively.
     * 
     * @param string $tagname The tag name to match
     * @return array[Sdl\SdlTag] The matching children
     */
    public function getChildrenByTagName($tagname)
    {
        return (array)array_map(function($tag) use($tagname) {
            if ($tag->getTagName() == $tagname)
            {
                return $tag;
            }
        }, $this->children);
    }
    
    /**
     * Add a child tag to the tag.
     * 
     * @param \Sdl\ISdlElement $tag Child tag to add
     * @return \Sdl\SdlTag
     */
    public function addChild(ISdlElement $tag)
    {
        if (!($tag instanceof SdlComment))
        {
            $tag->setParent($this);
        }
        $this->children[] = $tag;
        return $this;
    }
    
    /**
     * Create and return a new SDL root tag.
     * 
     * @return SdlTag A newly created root tag
     */
    public static function createRoot()
    {
        $tag = new self;
        return $tag;
    }
    
    /**
     * Create a new tag and add it as a child to the current tag.
     * 
     * @param string $tagname
     * @return SdlTag The newly created child tag
     */
    public function createChild($tagname)
    {
        $tag = new self;
        $tag->setTagName($tagname);
        $tag->setParent($this);
        $this->addChild($tag);
        return $tag;
    }
    
    /**
     * Create a new comment element and add it as a child to the current tag.
     * 
     * @param string $text The comment text
     * @return \Sdl\SdlTag
     */
    public function createComment($text)
    {
        $comment = new SdlComment();
        $comment->setValue($text);
        $this->addChild($comment);
        return $this;
    }
    
    /**
     * Return to the parent context (alias of getParent())
     * 
     * @return SdlTag The parent tag
     */
    public function end()
    {
        return $this->getParent();
    }
    
    /**
     * Return the parent tag.
     * 
     * @return SdlTag The parent tag
     */
    public function getParent()
    {
        return $this->parent_tag;
    }
    
    /**
     * Set the parent tag
     * 
     * @param \Sdl\SdlTag $parent The parent tag
     * @return \Sdl\SdlTag
     */
    public function setParent(SdlTag $parent)
    {
        $this->parent_tag = $parent;
        return $this;
    }
    
    /**
     * Encode the tag and its children into valid Sdl. Called by the encode()
     * method.
     * 
     * @return string
     */
    public function encodeTag()
    {
        $string = ($this->tag_namespace?":{$this->tag_namespace}":"")
                . ($this->tag_name)
                ;
        foreach($this->values as $value) {
            if ($value) {
                $string .= " {$value->getSdlLiteral()}";
            }
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
    
    /**
     * Encode the children of the tag into a string
     * 
     * @return string The encoded children of this tag
     */
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

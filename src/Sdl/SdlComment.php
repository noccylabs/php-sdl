<?php

/*
 * Copyright (C) 2014 noccy
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

/**
 * SDL Comment Element
 *
 * @author noccy
 */
class SdlComment implements ISdlElement
{
    const STYLE_PHP = "//";
    const STYLE_HASH = "#";
    const STYLE_DASH = "--";
    
    private $value;
    private static $comment_style = self::STYLE_HASH;
    
    /**
     * 
     * @return string Tag name is always "@COMMENT" for comments
     */
    public function getTagName()
    {
        return "@COMMENT";
    }
    
    /**
     * Set the comment style to one of the SdlComment::STYLE_* consts.
     * 
     * @param string $style The comment style to use (//, # or --)
     * @throws Exception
     */
    public static function setCommentStyle($style)
    {
        if (!in_array($style,[self::STYLE_PHP,self::STYLE_HASH,self::STYLE_DASH]))
        {
            throw new Exception("Invalid comment style");
        }
        self::$comment_style = $style;
    }
    
    /**
     * Set the comment text
     * 
     * @param string $value The comment text
     */
    public function setValue($value)
    {
        $this->value = (string)$value;
    }
    
    /**
     * Get the comment text
     * 
     * @return string The comment text
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * 
     * @return string The encoded comment tag
     */
    public function encodeTag()
    {
        return self::$comment_style." ".
                join("\n".self::$comment_style." ",explode("\n",$this->getValue(0)));
    }
}

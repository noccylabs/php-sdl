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

namespace Sdl\Selector;

use Sdl\SdlTag;

/**
 * Description of TagMatcher
 *
 * @author noccy
 */
class TagMatcher
{
    
    private $tag_match = null;
    private $expr_match = null;
    
    public function __construct($expression=null)
    {
        $this->setExpression($expression);
    }
    
    public function setExpression($expression)
    {
        $match = null;
        if (strpos($expression,"[")!==false)
        {
            list($this->tag_match,$this->expr_match) = explode("[",rtrim($expression,"]"));
        }
        else
        {
            $this->tag_match = $expression;
        }
    }
    
    public function match(SdlTag $tag)
    {
        if ($this->tag_match == $tag->getTagName())
        {
            return $this->matchExpression($tag);
        }
        return false;
    }
    
    private function matchExpression(SdlTag $tag)
    {
        if ($this->expr_match == null)
        {
            return true;
        }
        $expressionlanguage = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();
        $tag_wrapper = new TagWrapper($tag);
        return $expressionlanguage->evaluate(
            $this->expr_match, [
                "tag" => $tag_wrapper
            ]
        );
        
    }
    
}

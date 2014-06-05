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
 * Selects out tags from a tree of SDL tags. The expressions used are simple
 * and inspired by XPath and CSS selectors.
 * 
 * books {
 *   book title="Hello World" {
 *     isbn "01234567890123-4"
 *     author "John Doe"
 *   }
 * }
 * 
 * /books/book[@title="hello world"]  -- the book with matching title
 * /books/book  -- all books
 * /books/book[child.author.value()="John Doe"]  -- all books with author
 * 
 *
 * See docs for more
 */
class SdlSelector
{

    private $tag;
    
    public function __construct(SdlTag $tag)
    {
        $this->tag = $tag;
    }
    
    public function query($expression)
    {
        $exprs = []; $esc = 0; $expr = null;
        for($n = 0; $n < strlen($expression); $n++)
        {
            if (($expression[$n]=="/") && ($esc==0))
            {
                if ($expr)
                {
                    $exprs[] = $expr; $expr = null;
                }
            }
            elseif (($expression[$n]=="["))
            {
                $esc++;
                $expr.= $expression[$n];
            }
            elseif (($expression[$n]=="]"))
            {
                $esc--;
                $expr.= $expression[$n];
            }
            else 
            {
                $expr.= $expression[$n];
            }
        }
        if ($expr) {
            $exprs[] = $expr;
        }
        $ret = $this->selectTagsByExpressionStack($exprs,$this->tag);
        return $ret;
    }
    
    /**
     * Select tags using the TagSelector while traversing the tree, collecting
     * all matching tags.
     * 
     * 
     * @param array $exprs
     * @param \Sdl\SdlTag $context
     * @return type
     */
    protected function selectTagsByExpressionStack(array $exprs, SdlTag $context)
    {
        $select = array_shift($exprs);
        // Find item
        $matcher = new TagMatcher($select);
        $result = [];
        foreach($context->getAllChildren() as $tag)
        {
            if ($matcher->match($tag))
            {
                $result[] = $tag;
            }
        }
        // Recurse
        if (count($exprs)>0)
        {
            $output = [];
            foreach ($result as $resulttag)
            {
                $out = (array)$this->selectTagsByExpressionStack($exprs,$resulttag);
                $output = array_merge($output,$out);
            }
            return $output;
        }
        return $result;
    }
    
    public function queryOne($expression)
    {
        $query = $this->query($expression);
        if (count($query) > 0)
        {
            return $query[0];
        }
        return null;
    }
    
}

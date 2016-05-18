<?php

/* 
 * Copyright (C) 2014 NoccyLabs
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

namespace Sdl\Parser;

/**
 * This class wraps PHP tokens as returned by token_get_all.
 * 
 * 
 */
class TokenStream implements \Iterator, \Countable
{
    private $tokens = [];
    private $token_index = 0;

    public function __construct()
    {
    }

    /**
     * Parse the string using token_get_all
     * 
     * @param type $data
     */
    public function parseString($data)
    {
        $this->tokens = [];
        // Append a <?php so the tokenizer parses the data
        $trim_open = false;
        if (strpos($data,"<?php") === false)
        {
            $data = "<?php {$data}";
            $trim_open = true;
        }
        // Call the tokenizer
        $tokens = token_get_all($data);
        if ($trim_open)
        {
            $tokens = array_slice($tokens,1);
        }
        foreach($tokens as $token)
        {
            $this->tokens[] = ParserToken::createFromPhpToken($token);
        }
    }

    public function addToken(ParserToken $token)
    {
        $this->tokens[] = $token;
    }
    
    public function pushToken(ParserToken $token)
    {
        array_unshift($this->tokens, $token);
    }
    
    public function asString()
    {
        $out = null;
        foreach($this->tokens as $token)
        {
            $out .= $token->getString();
        }
        return $out;
    }
    
    public function asStringArray()
    {
        $out = [];
        foreach($this->tokens as $token)
        {
            $out[] = $token->getString();
        }
        return $out;
    }
    
    private function matchToken(ParserToken $token, $match)
    {
        if (is_array($match))
        {
            foreach($match as $item)
            {
                if ($this->matchToken($token,$item))
                {
                    return true;
                }
            }
            return false;
        }
        if (is_int($match))
        {
            return ($token->getToken() == $match);
        }
        if (is_string($match))
        {
            return ($token->getString() == $match);
        }
        return false;
    }
    
    /**
     * Return an array of tokens up until the one that does not match the
     * specified tokens.
     * 
     * @param type $match
     * @return type
     */
    public function getUntil($match)
    {
        $out = new TokenStream;
        while ($this->valid())
        {
            $current = $this->current();
            if ($this->matchToken($current,$match))
            {
                break;
            }
            $out->addToken($this->current());
            $this->next();
        }
        return $out;
    }
    
    public function filterTokens(callable $filter_function)
    {
        $token_list = new TokenStream;
        foreach($this->tokens as $token)
        {
            $tok_out = $filter_function($token);
            if ($tok_out)
            {
                $token_list->addToken($tok_out);
            }
        }
        return $token_list;
    }
    
    public function getWhile($match)
    {
        $out = new TokenStream;
        while ($this->valid())
        {
            $out->addToken($this->current());
            $this->next();
            $current = $this->current();
            if (!$this->matchToken($current,$match))
            {
                break;
            }
        }
        return $out;
    }
    
    public function count()
    {
        return count($this->tokens);
    }
    
    public function current()
    {
        if ($this->token_index < count($this->tokens))
        {
            return $this->tokens[$this->token_index];
        }
        return null;
    }

    public function next()
    {
        $this->token_index += 1;
    }

    public function key()
    {
        return $this->token_index;
    }

    public function valid()
    {
        return ($this->token_index < count($this->tokens));
    }

    public function rewind()
    {
        $this->token_index = 0;
    }

    public function getNext()
    {
        $next = $this->token_index + 1;
        if ($next < count($this->tokens))
        {
            return $this->tokens[$next];
        }
        return null;
    }
    
}

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

namespace Sdl\Parser;

/**
 * This class encapsulates a PHP tokenizer token.
 * 
 * 
 */
class Token
{
    private $token = null;
    private $string = null;
    private $line = null;

    /**
     * Constructor
     * 
     * @param int|null $token The token id (or null)
     * @param string|null $string The token string (or null)
     * @param int|null $line The line of the token (or null)
     */
    public function __construct($token=null, $string=null, $line=null)
    {
        $this->token = $token;
        $this->string = $string;
        $this->line = $line;
    }

    /**
     * Creates and returns a Token from the output of token_get_all
     * 
     * @static
     * @param string|array $token The token as a single string or array
     * @return \Sdl\Parser\Token
     */
    public static function createFromPhpToken($token)
    {
        if (is_array($token))
        {
            return new Token($token[0],$token[1],$token[2]);
        }
        else
        {
            return new Token(null, $token);
        }
        
    }
    
    /**
     * Set the token id
     * 
     * @param int $token The token id
     * @return \Sdl\Parser\Token
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
    
    /**
     * Set the token string
     * 
     * @param string $string The token string
     * @return \Sdl\Parser\Token
     */
    public function setString($string)
    {
        $this->string = $string;
        return $this;
    }
    
    /**
     * Set the line number
     * 
     * @param int $line The line number
     * @return \Sdl\Parser\Token
     */
    public function setLineNumber($line)
    {
        $this->line = $line;
        return $this;
    }
    
    /**
     * Return the token id
     * 
     * @return int The token id
     */
    public function getToken()
    {
        return $this->token;
    }
    
    /**
     * Return the token id as a string
     * 
     * @return string The token id as a string
     */
    public function getTokenName()
    {
        if (null !== $this->token)
        {
            return token_name($this->token);
        }
        return "NULL";
    }
    
    /**
     * Return the token string
     * 
     * @return string The token string
     */
    public function getString()
    {
        return $this->string;
    }
    
    /**
     * Return the line number of the token
     * 
     * @return int The line number
     */
    public function getLineNumber()
    {
        return $this->line;
    }

    /**
     * PHP string cast.
     * 
     * @return string String cast
     */
    public function __toString()
    {
        return $this->string;
    }

}

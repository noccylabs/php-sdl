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

use Sdl\LiteralType\TypeFactory;
use Sdl\SdlTag;
use Sdl\SdlUtils;

/**
 * Parses SDL into tag trees
 */
class SdlParser
{
    
    private $tokens = [];
    private static $cache_provider;
    
    const CONTENT_TAG = "content";
    const ROOT_TAG = "root";
    
    protected function __construct()
    {
    }

    /**
     * Parse a file.
     * 
     * @param string $file The file to parse
     * @return Sdl\SdlTag|null The document root tag
     */
    public static function parseFile($file)
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException("{$file} not found");
        }

        $string = file_get_contents($file);
        $parsed = self::parseString($string);
        return $parsed;
    }
    
    public static function parseString($string)
    {
        $parser = new self;
        $tokens = new TokenStream;
        $tokens->parseString($string);
        return $parser->parseFromTokenStream($tokens);
    }
    
    public function parseFromTokenStream(TokenStream $token_stream)
    {
        // Create new root node
        $root = new SdlTag;
        $root->setTagName(self::ROOT_TAG);
        // Rewind the token stream 
        $token_stream->rewind();
        // Clean up the stream
        $sdl_tokens = $token_stream->filterTokens(function($token) {
            $str = $token->getString();
            if (strpos($str, "\n") !== false) {
                $token->setString("\n");
            }
            elseif (trim($str)=="")
            {
                return null;
            }
            return $token;
        })->asStringArray();
        // process namespaces, merge them with the keys
        $out_tokens = []; $last=null;
        foreach($sdl_tokens as $token)
        {
            if ($last == ":") 
            {
                $out_tokens[count($out_tokens)-1].=":".$token;
            }
            elseif ($token == ":")
            {
                // skip
            }
            else
            {
                $out_tokens[] = $token;
            }
            $last = $token;
        }
        
        $this->tokens = $out_tokens;
        while (count($this->tokens) > 0)
        {
            $this->parseTokens($root); // , $out_tokens);
        }
        
        return $root;
        
    }

    private function parseTokens(SdlTag &$tag)
    {
        $_ = [null,[],[]];
        while (count($this->tokens) > 0)
        {
            $tok = array_shift($this->tokens);
            if (($tok == ";") || ($tok == "\n") || ($tok == "{"))
            {
                if ($_[0])
                {
                    $_tag = new SdlTag($_[0]);
                    $_[1] = array_map("Sdl\\LiteralType\\TypeFactory::createFromString", $_[1]);
                    foreach($_[2] as $k=>$v)
                    {
                        $_tag->setAttribute($k, TypeFactory::createFromString($v));
                    }
                    $_tag->setValuesFromArray($_[1]);
                    $tag->addChild($_tag);
                    //echo "{$_[0]}:\n"; var_dump($_[1]); var_dump($_[2]); echo "\n\n";
                }
                $_ = [null,[],[]];
                if ($tok == "{")
                {
                    $this->parseTokens($_tag);
                }
            }
            elseif ($tok == "}")
            {
                return;
            }
            elseif ($_[0]==null)
            {
                if (SdlUtils::isValidIdentifier($tok))
                {
                    $_[0] = $tok;
                }
                else
                {
                    $_[0] = "value";
                    $_[1][] = $tok;
                }
            }
            else
            {
                if ($tok == "=")
                {
                    $attr_name = array_pop($_[1]);
                    $val = array_shift($this->tokens);
                    $_[2][$attr_name] = $val;
                }
                else 
                {
                    $_[1][] = $tok;
                }
            }
        }
    }

}

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

namespace Sdl\Parser;

use Sdl\SdlTag;
use Sdl\Exception\ParserException;
use Sdl\LiteralType\TypeFactory;

/**
 * Description of SdlParser
 *
 * @author noccy
 */
class SdlParser
{

    private $name       = null;
    private $values     = [];
    private $attr       = [];
    private $children   = [];
    private $comment    = null;
    private $doccomment = null;
    private $ns         = null;
    private $parent     = null;

    /** Strict parsing according to SDL 1.2 */
    const OPT_STRICT = 0x01;

    const PARSER_TAGNAME = 0;
    const PARSER_TAGVALUE = 1;
    const PARSER_TAGATTR = 2;
    
    public static function parseString($str, SdlTag $sdl_tag=null)
    {

        if (!$sdl_tag)
        {
            $sdl_tag = SdlTag::createRoot();
        }
        
        static $level = 0;

        if (!is_array($str))
        {
            $toks = token_get_all("<?php " . $str);
            // Get rid of the opening tag
            array_shift($toks);
            $level = 0;
        } else
        {
            $toks = $str;
            $level++;
        }

        // Helpers

        $pstate = self::PARSER_TAGNAME; // parser state, what we are expecting
        $buf = null; // Holding the current buffer
        $lasttok = null; // Holding the last token for attr assignment
        $tagname = null; // The parsed tag name
        $tagvals = []; // The tag values
        $tagattr = []; // The tag attributes
        $tagcmt = null;
        $tagdcmt = null;
        $break = false; // flag to indicate end of tag
        $lline = "n/a";
        $toktyp = null;

        while (count($toks) > 0)
        {
            $thistok = array_shift($toks);
            // Get the string representation of the token
            if (is_array($thistok))
            {
                list($toktyp, $thisstr, $lline) = $thistok;
                switch ($toktyp)
                {
                    case T_COMMENT:
                        $thiscmt = trim(substr($thisstr, 2));
                        if ($tagcmt)
                        {
                            if (!$thiscmt)
                                $tagcmt.="\n";
                            else
                                $tagcmt.=" " . $thiscmt;
                        } else
                        {
                            $tagcmt = $thiscmt;
                        }
                        $thisstr = null;
                        break;
                    case T_DOC_COMMENT:
                        $tagdcmt = $thisstr;
                        $thisstr = null;
                        break;
                }
                //echo token_name($toktyp)."\n";
            } else
            {
                $thisstr = $thistok;
                $toktyp = null;
            }
            // we do this to only detect newlines, we don't care about the
            // padding around it.
            if (strpos($thisstr, "\n") !== false)
            {
                if (!trim($thisstr, "\n\r "))
                    $thisstr = "\n";
            }
            // Replace tabs
            if (strpos($thisstr, "\t") !== false)
                $thisstr = str_replace("\t", " ", $thisstr);

            // Parse the tokens
            $break = false;
            //echo "\033[1m{$level}\033[0m\033[7m{$thisstr}\033[0m\n";
            $thisstr = trim($thisstr, " ");
            switch ($thisstr)
            {
                case "}":
                    if ($level <= 0)
                        throw new ParserException("Recursion level mismatch on '}'", ParserException::ERR_RECURSION_MISMATCH);
                    //echo "Leaving child...\n";
                    $pstate = self::PARSER_TAGNAME;
                    $level--;
                    return $toks;
                    //echo "Ascend: {$buf}\n";
                    $buf = null;
                    // ascend
                    break;
                case ";":
                case "\n":
                case "\r":
                case "{":
                    $break = true;

                case "":
                    if (!$break)
                    {
                        // is this part of a date?
                        $next = $toks[0];
                        if (is_array($next))
                            $next = $next[1];
                        $next2 = $toks[1];
                        if (is_array($next2))
                            $next2 = $next2[1];
                        $next.= $next2;
                        if ((preg_match("/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/", $buf) && (preg_match("/^[0-9]{2}:/", $next))))
                        {
                            $buf.=" ";
                            break;
                        }
                    }
                    // If this is a binary chunk we want to keep reading til "]"
                    if (substr(trim($buf), 0, 1) == "[")
                    {
                        if (substr(trim($buf), -1, 1) != "]")
                        {
                            $break = false;
                            break;
                        }
                        // If we make it here we have a full binary blob
                    }
                    if (trim($buf))
                    {
                        if ($pstate == self::PARSER_TAGATTR)
                        {
                            // Found a tag attribute
                            $lt = TypeFactory::createFromString($buf);
                            if (!$lt)
                                throw new SdlParserException("Unparsed attribute value: {$buf}");
                            $tagattr[$lasttok] = $lt;
                            //echo "  Attr: {$lasttok} = {$buf}\n";
                            $pstate = self::PARSER_TAGVALUE;
                        } elseif ($pstate == self::PARSER_TAGVALUE)
                        {
                            // Found a tag value
                            $tv = TypeFactory::createFromString($buf);
                            if (!$tv)
                                throw new SdlParserException("Unparsed value: {$buf}");
                            $tagvals[] = $tv;
                            //echo "  Value: {$buf} parsed as {$tv}\n";
                        } elseif ($pstate == self::PARSER_TAGNAME)
                        {
                            // Found a tag name, inspect and see if it is a valid
                            // tag name, and if not create an anonymous tag.
                            if (self::isValidIdentifier(trim($buf)))
                            {
                                $tagname = trim($buf);
                                //echo "Tag: {$buf}\n";
                            } else
                            {
                                $tagname = null;
                                $tagvals[] = TypeFactory::createFromString($buf);
                                //echo "(anon)\n  Value: {$buf}\n";
                            }
                            $pstate = self::PARSER_TAGVALUE;
                        }
                    }
                    if ($thisstr == "{")
                    {
                        //echo "Got { ... \n"; var_dump($tagname);
                        //echo "Entering child...\n";
                        //var_dump($tagvals);
                        if (!empty($tagname) || !empty($tagvals))
                        {
                            $tag = new SdlTag($tagname, $tagvals, $tagattr);
                            //$tag->setComment($tagcmt);
                            //$tag->setDocComment($tagdcmt);
                            $toks = self::parseString($toks);
                        }
                        $break = true;
                    } elseif ($break)
                    {
                        //echo "Got ; ... \n"; var_dump($tagname);
                        //var_dump($tagvals);
                        if (!empty($tagname) || !empty($tagvals))
                        {
                            $tag = new SdlTag($tagname, $tagvals, $tagattr);
                        }
                    } else
                    {
                        $tag = null;
                    }
                    // If we are at the end of the tag, reset the state
                    if ($break)
                    {
                        if (!empty($tag))
                            $sdl_tag->addChild($tag);
                        $tag = null;
                        $tagname = null;
                        $tagvals = [];
                        $tagattr = [];
                        $tagcmt = null;
                        $tagdcmt = null;
                        $pstate = self::PARSER_TAGNAME;
                        $break = false;
                    }
                    $buf = null;
                    // new state
                    break;
                case "=":
                    // Remember the last token and set the parser state to
                    // expect an attribute value.
                    if (substr($buf, 0, 1) == "[")
                    {
                        // If we are in a binary chunk, just stash and break out
                        $buf.=$thisstr;
                        break;
                    }
                    if (self::isValidIdentifier($buf))
                    {
                        $lasttok = $buf;
                        $pstate = self::PARSER_TAGATTR;
                    } else
                    {
                        throw new ParserException("Invalid identifier '{$buf}' used as attribute near line {$lline}", SdlParserException::ERR_INVALID_IDENTIFIER);
                    }
                    $buf = null;
                    // attribute value asign
                    break;
                default:
                    //echo "Pushing to buffer: {$thisstr}\n";
                    $buf.= $thisstr;
            }
        }
        
        return $sdl_tag;
    }

    /**
     * Check if the name is a valid identifier according to SDL 1.2
     *
     */
    public static function isValidIdentifier($name) {
        // From the SDL language guide: An SDL identifier starts with a unicode
        // letter or underscore (_) followed by zero or more unicode letters,
        // numbers, underscores (_), dashes (-), periods (.) and dollar signs
        // ($).
        return (preg_match("/^[_a-zA-Z]{1}[_\-\.\$a-zA-Z0-9]*/", $name));

    }
    
    
}

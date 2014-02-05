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

use Sdl\Parser\ParserToken;

/**
 * The TypeFactory is used to convert tokens (raw strings, with or without
 * quotes depending on type) into their respective LiteralType derived classes.
 * The mapping is done in a dynamic way, with the classes registered by the
 * registerDefaultTypes() and/or registerLiteralType() static methods.
 * 
 * To convert a SDL literal token, use the createFromString() method.
 * 
 * It can also be used to convert PHP variables into their respective SDL type
 * using the createFromPhpValue() method.
 * 
 */
abstract class TypeFactory
{



    private static $types = [];
    private static $php_types = [];
    
    /**
     * Register default types for the parser. Should be called manually if you
     * wish to append (or prepend) your own literal types to the list of
     * known types.
     * 
     *   OK  string
     *   OK  boolean
     *       const RE_RAWSTRING = "/^`(.*)`$/ms";
     *       const RE_BINARY = "/^\[(.*)\]$/m";
     *       const RE_CHAR = "/^\'.\'$/";
     *   OK  integer
     *       const RE_LONGINT = "/^[\+\-]{0,1}[\.0-9]*l$/i";
     *   OK  float
     *   OK  double
     *       decimal
     *       const RE_DATETIME = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2}(\.[0-9]{1,3}([\-]{0,1}.*)?)?)?$/";
     *       const RE_DATE = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/";
     *       const RE_TIME = "/^([0-9]{0,5}d\:){0,1}([0-9]{0,5}:){0,1}([0-9]{1,2}):([0-9]{1,2})(\.[0-9]{1,3})?$/";
     * 
     */
    public static function registerDefaultTypes()
    {
        //self::registerLiteralType("Sdl\\LiteralType\\SdlBinary");
        self::registerLiteralType("Sdl\\LiteralType\\SdlBoolean",  "boolean");
        self::registerLiteralType("Sdl\\LiteralType\\SdlString",   "string");
        //self::registerLiteralType("Sdl\\LiteralType\\SdlRawString");
        //self::registerLiteralType("Sdl\\LiteralType\\SdlCharacter");
        //self::registerLiteralType("Sdl\\LiteralType\\SdlLongInt");
        self::registerLiteralType("Sdl\\LiteralType\\SdlInteger",  "integer");
        self::registerLiteralType("Sdl\\LiteralType\\SdlFloat");
        self::registerLiteralType("Sdl\\LiteralType\\SdlDecimal");
        self::registerLiteralType("Sdl\\LiteralType\\SdlDouble",   "double");
        //self::registerLiteralType("Sdl\\LiteralType\\SdlDateTime");
        self::registerLiteralType("Sdl\\LiteralType\\SdlDate");
        //self::registerLiteralType("Sdl\\LiteralType\\SdlTimeSpan");
    }
    
    /**
     * Register a class as a literal type. 
     * 
     * @param type $class
     * @throws \Sdl\Exception\SdlException
     */
    public static function registerLiteralType($class,$php_type=null)
    {
        if (get_parent_class($class) != "Sdl\\LiteralType\\LiteralType")
        {
            throw new \Sdl\Exception\SdlException("Class passed to registerLiteralType must extend Sdl\\LiteralType\\LiteralType.");
        }
        if (empty($class::$match_pattern))
        {
            throw new \Sdl\Exception\SdlException("LiteralType needs static property \$match_pattern");
        }
        self::$types[$class::$match_pattern] = $class;
        if ($php_type)
        {
            self::$php_types[$php_type] = $class;
        }
    }

    /**
     * Convert a parser token into a LiteralToken.
     * 
     * @param \Sdl\Parser\ParserToken $token
     * @return SdlLiteral The literal, from createFromString
     */
    public static function createFromToken(ParserToken $token)
    {
        return self::createFromString($token->getString());
    }

    /**
     * Convert a string into a LiteralType. If a LiteralType is passed, it will
     * be returned unmodified. 
     * 
     * If no types have been registered with registerLiteralType() or
     * registerDefaultTypes(), registerDefaultTypes() will be called automatically.
     * 
     * @param mixed|\Sdl\LiteralType\LiteralType $value The value to convert into a LiteralType
     * @return \Sdl\LiteralType\LiteralType|null The LiteralType
     */
    public static function createFromString($value)
    {
        if ($value instanceof LiteralType)
        {
            return $value;
        }
        
        if (count(self::$types) == 0)
        {
            self::registerDefaultTypes();
        }
        
        foreach(self::$types as $match=>$class)
        {
            if (preg_match($match, $value))
            {
                return $class::fromLiteral($value);
            }
        }

        // This should be a typeexception!
        error_log("No matching literal type for '{$value}'");
        return null;
        
    }
    
    /*
        // TODO: Pay attention to quoting and parse non-quoted tokens
        //echo "[Parsing value string '{$value}']\n";
        if (($value == "true") || ($value == "yes") || ($value == "on"))
        {
            return new SdlBoolean(true);
        }
        elseif ($value == "null")
        {
            return new SdlNull();
        }
        
        
        elseif ((preg_match(self::RE_BINARY, $value)) && ($raw))
        {
            //base64_decode(substr($value,1,strlen($value)-2))
            return SdlBinary::fromToken($value);
        }
        elseif (preg_match(self::RE_RAWSTRING, $value))
        {
            $strval = substr($value, 1, strlen($value) - 2);
            return new self(stripcslashes($strval), self::LT_STRING, $value, $name);
        }
        
        if (preg_match(self::RE_STRING, $value))
        {
            $strval = substr($value, 1, strlen($value) - 2);
            if (strpos($strval, "\n") !== false)
            {
                $strs = explode("\n", $strval);
                $stro = array_shift($strs);
                foreach ($strs as $str)
                {
                    $stro = rtrim(rtrim($stro), "\\") . ltrim($str);
                }
                $strval = $stro;
            }
            return new SdlString(stripcslashes($strval));
        }
        
        elseif (preg_match(self::RE_DATETIME, $value))
        {
            $match = null;
            preg_match_all(self::RE_DATETIME, $value, $match);
            list($year, $month, $day) = [(int) $match[1][0], (int) $match[2][0], (int) $match[3][0]];
            list($hour, $minute, $second) = [(int) $match[4][0], (int) $match[5][0], $match[6][0]];
            list($micro, $tz) = [(float) $match[7][0], (string) $match[8][0]];
            if ($second)
                $second = (int) substr($second, 1);
            else
                $second = 0;
            if ($tz && (!defined("SDL_IGNORE_TIMEZONE")))
            {
                throw new SdlParserException("Timezones for dates are not implemented. Define SDL_IGNORE_TIMEZONE to disable this exception.", SdlParserException::ERR_NOT_IMPLEMENTED);
            }
            // TODO: Implement timezones
            $ts = mktime($hour, $minute, $second, $month, $day, $year); //
            $ts+= $micro;
            //echo "{$value} =>\n Y: {$year}\n M: {$month}\n D: {$day}\n H: {$hour}\n M: {$minute}\n S: {$second}\n µ: {$micro}\n TZ: {$tz}\n ==> {$ts}\n";
            return new SdlTypedValue($ts, self::LT_DATETIME, $value, $name);
        }
        elseif (preg_match(self::RE_TIME, $value))
        {
            $match = null;
            preg_match_all(self::RE_TIME, $value, $match);
            list($days, $hours, $minutes, $seconds, $micro) = [(int) $match[1][0], (int) $match[2][0], (int) $match[3][0], (int) $match[4][0], (float) $match[5][0]];
            $time = ($seconds) + ($minutes * 60) + ($hours * 60 * 60) + ($days * 60 * 60 * 24);
            if ($micro)
                $time += $micro;
            // echo "{$value} =>\n H: {$hours}\n M: {$minutes}\n S: {$seconds}\n ==> {$time}\n";
            return new SdlTypedValue($time, self::LT_TIMESPAN, $value, $name);
        }

        error_log("Warning: Value type could not be determined for '{$value}'\n");
        return new SdlString(stripcslashes($value));
        
    }
    */

    /**
     * Return a LiteralType derived instance representing the provided PHP
     * value.
     * 
     * If no types have been registered with registerLiteralType() or
     * registerDefaultTypes(), registerDefaultTypes() will be called automatically.
     * 
     * @param mixed $var The PHP value to convert into a LiteralType
     * @return \Sdl\LiteralType\LiteralType The LiteralValue
     */
    public static function createFromPhpValue($var)
    {

        // Register default types if there are no types loaded.
        if (count(self::$types) == 0)
        {
            self::registerDefaultTypes();
        }
        
        // Get the var type and check the known types to find a match
        $type = gettype($var);
        if (array_key_exists($type,self::$php_types))
        {
            $class = self::$php_types[$type];
            return new $class($var);
        }
        
        // This should be a typeexception!
        error_log("No matching literal type for type {$type}");
        var_dump(self::$php_types);
        return null;
        
     }
 

}

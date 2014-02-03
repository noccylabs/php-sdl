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

use Sdl\Parser\Token;

abstract class TypeFactory
{

    const RE_STRING = "/^\"(.*)\"$/ms";
    const RE_RAWSTRING = "/^`(.*)`$/ms";
    const RE_BINARY = "/^\[(.*)\]$/m";
    const RE_CHAR = "/^\'.\'$/";
    const RE_INT = "/^[\+\-]{0,1}[0-9]*$/";
    const RE_LONGINT = "/^[\+\-]{0,1}[\.0-9]*l$/i";
    const RE_FLOAT = "/^[\+\-]{0,1}[0-9]*f$/i";
    const RE_DFLOAT = "/^[\+\-]{0,1}[0-9]*\.[0-9]*[d]?$/i";
    const RE_DECIMAL = "/^[\+\-]{0,1}[\.0-9]*bd$/i";
    const RE_DATETIME = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2}(\.[0-9]{1,3}([\-]{0,1}.*)?)?)?$/";
    const RE_DATE = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/";
    const RE_TIME = "/^([0-9]{0,5}d\:){0,1}([0-9]{0,5}:){0,1}([0-9]{1,2}):([0-9]{1,2})(\.[0-9]{1,3})?$/";

    public static function createFromToken(Token $token)
    {
        return self::createFromString($token->getString());
    }

    public static function createFromString($value)
    {
        if ($value instanceof LiteralType)
        {
            return $value;
        }
        
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
        
        
        /*
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
        */
        
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
        elseif (preg_match(self::RE_CHAR, $value))
        {
            return new SdlChar(stripcslashes(substr($value, 1, strlen($value) - 2)));
        }
        elseif (preg_match(self::RE_DECIMAL, $value))
        {
            return new SdlDecimal($value);
        }
        elseif (preg_match(self::RE_DFLOAT, $value))
        {
            return new SdlDouble($value);
        }
        elseif (preg_match(self::RE_FLOAT, $value))
        {
            return new SdlFloat(floatval($value));
        }
        elseif (preg_match(self::RE_INT, $value))
        {
            return new SdlInteger(intval($value));
        }
        elseif (preg_match(self::RE_LONGINT, $value))
        {
            return new SdlLong(intval($value));
        }
        elseif (preg_match(self::RE_DATE, $value))
        {
            return new SdlDate($value, true);
        }
        
        /*
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
        */

        error_log("Warning: Value type could not be determined for '{$value}'\n");
        return new SdlString(stripcslashes($value));
        
    }

    public static function createFromPhpValue($var)
    {
        if (is_float($var))
        {
            return new SdlFloat($var);
        } elseif (is_double($var))
        {
            return new SdlDouble($var);
        } elseif (is_int($var))
        {
            return new SdlInteger($var);
        } elseif (is_bool($var))
        {
            return new SdlBoolean($var);
        } elseif (is_string($var))
        {
            return new SdlString($var);
        }
    }

}

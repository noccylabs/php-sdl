<?php

namespace Sdl;

class SdlTypedValue {
    // Literal types - U = unsupported, P = partial support
    const   LT_STRING   = 1;  //     "string" or `string`
    const   LT_CHAR     = 2;  // [U] Character as 'c'
    const   LT_INT      = 3;  //     123
    const   LT_LONGINT  = 4;  // [U] 123L or 123l
    const   LT_FLOAT    = 5;  // [U] 123.45F or 123.45f
    const   LT_DFLOAT   = 6;  // [P] 123.45 or 123.45d or 123.45D
    const   LT_DECIMAL  = 7;  // [U] 123.45BD or 123.45bd
    const   LT_BOOLEAN  = 8;  //     Boolean, yes no or true false
    const   LT_DATE     = 9;  // [U] YYYY/MM/DD
    const   LT_DATETIME = 10; // [U] yyyy/mm/dd hh:mm(:ss)(.xxx)(-ZONE)
    const   LT_TIMESPAN = 11; // [U] (d'd':)hh:mm:ss(.xxx)
    const   LT_BINARY   = 12; //     [base64data]
    const   LT_NULL     = 13; //     null

    // Keywords to expand
    private static $kwexpand = [
        "true" => true,
        "yes" => true,
        "on" => true,
        "false" => false,
        "off" => false,
        "no" => false,
        "null" => null
    ];

    public $name;
    public $ns;
    public $nsurl;
    private $type;
    private $value;
    private $source;

    public function __construct($value,$type=null,$source=null,$name=null) {
        $this->value = $value;
        $this->source = $source;
        if ($type)
            $this->type = $type;
        else
            $this->type = $this->detectType($value);
        if ($name) {
            if (strpos($name,":")!==false) {
                list($ns,$name) = explode(":",$name,2);
            } else {
                $ns = null;
            }
            $this->name = $name;
            $this->ns = $ns;
        }
    }

    public function __toString() {
        return "<".$this->value.">";
    }

    const RE_STRING     = "/^\"(.*)\"$/ms";
    const RE_RAWSTRING  = "/^`(.*)`$/ms";
    const RE_BINARY     = "/^\[(.*)\]$/m";
    const RE_CHAR       = "/^\'.\'$/";
    const RE_INT        = "/^[\+\-]{0,1}[0-9]*$/";
    const RE_LONGINT    = "/^[\+\-]{0,1}[\.0-9]*l$/i";
    const RE_FLOAT      = "/^[\+\-]{0,1}[\.0-9]*f$/i";
    const RE_DFLOAT     = "/^[\+\-]{0,1}[\.0-9]*[d]?$/i";
    const RE_DECIMAL    = "/^[\+\-]{0,1}[\.0-9]*bd$/i";
    const RE_DATETIME   = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2}(\.[0-9]{1,3}([\-]{0,1}.*)?)?)?$/";
    const RE_DATE       = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/";
    const RE_TIME       = "/^([0-9]{0,5}d\:){0,1}([0-9]{0,5}:){0,1}([0-9]{1,2}):([0-9]{1,2})(\.[0-9]{1,3})?$/";

    public function getValue() {
        return $this->value;
    }

    public function setDate($value) {
        if (is_string($value)) {
            $this->value = strtotime($value);
        } else {
            $this->value = (float)$value;
        }
        $this->type = self::LT_DATE;
    }

    public function setDateTime($value) {
        if (is_string($value)) {
            $this->value = strtotime($value);
        } else {
            $this->value = (float)$value;
        }
        $this->type = self::LT_DATE;
    }

    /**
     * Change the value while preserving the type if possible.
     */
    public function setValue($value,$type=null) {
        if ($type)
            $this->type = $type;
        switch($this->type) {
            case self::LT_DECIMAL:
            case self::LT_DFLOAT:
            case self::LT_FLOAT:
                if (is_float($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_INT:
            case self::LT_LONGINT:
                if (is_int($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_BOOLEAN:
                if (is_bool($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_BINARY:
                $this->value = $value;
                return;
        }
        $this->value = $value;
        if (is_float($value)) {
            $this->type = self::LT_FLOAT;
        } elseif (is_bool($value)) {
            $this->type = self::LT_BOOLEAN;
        } else {
            $this->type = self::LT_STRING;
        }

    }

    public static function parse($value,$raw=false,$name=null) {
        if ($value instanceof SdlTypedValue)
            return $value;
        // TODO: Pay attention to quoting and parse non-quoted tokens
        //echo "[Parsing value string '{$value}']\n";
        if (($value === true) || ($value === false)) {
            return new self($value,self::LT_BOOLEAN,$value,$name);
        } elseif ($value === null) {
            return new self($value,self::LT_NULL,$value,$name);
        } elseif ((preg_match(self::RE_BINARY, $value)) && ($raw)) {
            return new self(base64_decode(substr($value,1,strlen($value)-2)), self::LT_BINARY, $value,$name);
        } elseif (preg_match(self::RE_RAWSTRING, $value)) {
            $strval = substr($value,1,strlen($value)-2);
            return new self(stripcslashes($strval), self::LT_STRING, $value,$name);
        } elseif (preg_match(self::RE_STRING, $value)) {
            $strval = substr($value,1,strlen($value)-2);
            if (strpos($strval,"\n")!==false) {
                $strs = explode("\n",$strval);
                $stro = array_shift($strs);
                foreach($strs as $str) {
                    $stro = rtrim(rtrim($stro),"\\").ltrim($str);
                }
                $strval = $stro;
            }
            return new self(stripcslashes($strval), self::LT_STRING, $value,$name);
        } elseif (preg_match(self::RE_CHAR, $value)) {
            return new self(stripcslashes(substr($value,1,strlen($value)-2)), self::LT_CHAR, $value,$name);
        } elseif (preg_match(self::RE_DECIMAL, $value)) {
            return new self(floatval($value), self::LT_DECIMAL, $value,$name);
        } elseif (preg_match(self::RE_DFLOAT, $value)) {
            return new self(floatval($value), self::LT_DFLOAT, $value,$name);
        } elseif (preg_match(self::RE_FLOAT, $value)) {
            return new self(floatval($value), self::LT_FLOAT, $value,$name);
        } elseif (preg_match(self::RE_INT, $value)) {
            return new self(intval($value), self::LT_INT, $value,$name);
        } elseif (preg_match(self::RE_LONGINT, $value)) {
            return new self(intval($value), self::LT_LONGINT, $value,$name);
        } elseif (preg_match(self::RE_DATETIME, $value)) {
            $match = null;
            preg_match_all(self::RE_DATETIME, $value, $match);
            list($year,$month,$day) = [(int)$match[1][0],(int)$match[2][0],(int)$match[3][0]];
            list($hour,$minute,$second) = [(int)$match[4][0],(int)$match[5][0],$match[6][0]];
            list($micro,$tz) = [(float)$match[7][0], (string)$match[8][0]];
            if ($second) $second = (int)substr($second,1); else $second = 0;
            if ($tz && (!defined("SDL_IGNORE_TIMEZONE"))) {
                throw new SdlParserException("Timezones for dates are not implemented. Define SDL_IGNORE_TIMEZONE to disable this exception.", SdlParserException::ERR_NOT_IMPLEMENTED);
            }
            // TODO: Implement timezones
            $ts = mktime($hour,$minute,$second,$month,$day,$year); //
            $ts+= $micro;
            //echo "{$value} =>\n Y: {$year}\n M: {$month}\n D: {$day}\n H: {$hour}\n M: {$minute}\n S: {$second}\n µ: {$micro}\n TZ: {$tz}\n ==> {$ts}\n";
            return new SdlTypedValue($ts, self::LT_DATETIME, $value,$name);
        } elseif (preg_match(self::RE_DATE, $value)) {
            $match = null;
            preg_match_all(self::RE_DATE, $value, $match);
            list($year,$month,$day) = [$match[1][0],$match[2][0],$match[3][0]];
            // TODO: Implement timezones
            $ts = mktime(0,0,0,$month,$day,$year); //
            return new SdlTypedValue($ts, self::LT_DATE, $value,$name);
        } elseif (preg_match(self::RE_TIME, $value)) {
            $match = null;
            preg_match_all(self::RE_TIME, $value, $match);
            list($days,$hours,$minutes,$seconds,$micro) = [(int)$match[1][0],(int)$match[2][0],(int)$match[3][0],(int)$match[4][0],(float)$match[5][0]];
            $time = ($seconds) + ($minutes*60) + ($hours*60*60) + ($days*60*60*24);
            if ($micro) $time += $micro;
            // echo "{$value} =>\n H: {$hours}\n M: {$minutes}\n S: {$seconds}\n ==> {$time}\n";
            return new SdlTypedValue($time, self::LT_TIMESPAN, $value,$name);
        } elseif ((array_key_exists($value,self::$kwexpand)) && $raw) {
            $value = self::$kwexpand[$value];
            if (is_bool($value)) {
                return new SdlTypedValue($value,self::LT_BOOLEAN, $value,$name);
            } elseif (is_null($value)) {
                return new SdlTypedValue($value,self::LT_NULL, $value,$name);
            }
        } else {
            //echo "Warning: Value type of {$value} not determined.\n";
            return new SdlTypedValue(stripcslashes($value),self::LT_STRING, $value,$name);
            //fprintf(STDERR,"Warning: Value type could not be determined for '{$value}'\n");
        }
    }

    public function encode() {
        //echo "Type:{$this->type} / Value:{$this->value}\n";
        switch($this->type) {
            case self::LT_BINARY:
                return "[".wordwrap(base64_encode($this->value),64,"\n",true)."]";
            case self::LT_BOOLEAN:
                return ($this->value)?'true':'false';
            case self::LT_NULL:
                return "null";
            case self::LT_STRING:
                if (strpos($this->value,"\n")) {
                    $str = addslashes($this->value);
                    return "`".$str."`";
                } else {
                    $str = addslashes($this->value);
                    return "\"".$str."\"";
                }
            case self::LT_CHAR:
                return "'".substr($this->value,0,1)."'";

            case self::LT_DECIMAL:
                return $this->value."bd";
            case self::LT_DFLOAT:
                return $this->value;
            case self::LT_FLOAT:
                return $this->value."f";
            case self::LT_INT:
                return $this->value;
            case self::LT_LONGINT:
                return $this->value."l";

            case self::LT_TIMESPAN:
                $h = floor($this->value / 3600);
                $m = floor(($this->value / 60) % 60);
                $s = $this->value % 60;
                $u = $this->value - intval($this->value);
                $us = ($u)?substr($u,1,4):"";
                if ($h > 24) {
                    $d = floor($h / 24);
                    $h = $h - ($d * 24);
                    return sprintf("%dd:%02d:%02d:%02d%s",$d,$h,$m,$s,$us);
                } else {
                    return sprintf("%02d:%02d:%02d%s",$h,$m,$s,$us);
                }
            case self::LT_DATE:
                return date("Y/m/d", $this->value);
            case self::LT_DATETIME:

        }
        return $this->source;
    }

}

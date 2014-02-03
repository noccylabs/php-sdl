<?php
/**
 * 
 * 
 */

require_once __DIR__."/../vendor/autoload.php";

use Sdl\Parser\SdlParser;

$sdl = file_get_contents(__DIR__."/basic.sdl");

$tag = SdlParser::parseString($sdl);

echo $tag->encode();

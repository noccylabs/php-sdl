<?php
/**
 * 
 * 
 */

require_once __DIR__."/../vendor/autoload.php";

use Sdl\Parser\SdlParser;

$tag = SdlParser::parseFile(__DIR__."/complex.sdl");

echo $tag->encode();

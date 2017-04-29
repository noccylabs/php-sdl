<?php

require_once __DIR__."/../vendor/autoload.php";

use Sdl\SdlTag;
use Sdl\Script\SdlScript;
use Sdl\Parser\SdlParser;

$root = SdlParser::parseFile(__DIR__."/script.sdl");

$script = new SdlScript();
$script->addDefaultFunctions();
$script->addFunction("hello", function ($script,$tag,$name,array $values,array $keys) {
    echo "Hello, ".$values[0]."!\n";
});
$script->evaluate($root);

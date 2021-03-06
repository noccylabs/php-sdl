<?php

namespace Sdl\Parser;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-02-05 at 02:46:51.
 */
class SdlParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @covers Sdl\Parser\SdlParser::parseFile
     * @covers Sdl\Parser\SdlParser::parseFromTokenStream
     */
    public function testParseFile()
    {
        $file = __DIR__."/../../../../examples/sdl/complex.sdl";
        $sdl = file_get_contents($file);
        $tag = SdlParser::parseFile($file);
        $this->assertInstanceOf("Sdl\\SdlTag", $tag);
        $this->assertEquals($sdl,$tag->encode());
    }

    /**
     * @covers Sdl\Parser\SdlParser::parseString
     * @covers Sdl\Parser\SdlParser::parseFromTokenStream
     */
    public function testParseString()
    {
        $file = __DIR__."/../../../../examples/sdl/complex.sdl";
        $sdl = file_get_contents($file);
        $tag = SdlParser::parseString($sdl);
        $this->assertInstanceOf("Sdl\\SdlTag", $tag);
        $this->assertEquals($sdl,$tag->encode());
    }

}

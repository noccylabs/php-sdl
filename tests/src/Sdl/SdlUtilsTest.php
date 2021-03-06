<?php

namespace Sdl;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-02-06 at 04:40:01.
 */
class SdlUtilsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SdlUtils
     */
    protected $object;

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
     * @covers Sdl\SdlUtils::isValidIdentifier
     */
    public function testIsValidIdentifier()
    {
        $valid = [ 
            "foo:bar", "Foo:Bar", "Foo:b.ar$", "foob-ar$", "_fizz"
        ];
        $invalid = [
            "@foo", "~far", "!faz"
        ];
        
        foreach($valid as $identifier)
        {
            $this->assertEquals(true,SdlUtils::isValidIdentifier($identifier));
        }
        
        foreach($invalid as $identifier)
        {
            $this->assertEquals(false,SdlUtils::isValidIdentifier($identifier));
        }
    }

}

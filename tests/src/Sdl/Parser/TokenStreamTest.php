<?php

namespace Sdl\Parser;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-01-31 at 22:49:06.
 */
class TokenStreamTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var TokenStream
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new TokenStream;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @covers Sdl\Parser\TokenStream::parseString
     * @todo   Implement testParseString().
     */
    public function testParseString()
    {
        $this->object->parseString("foo;");
        $this->assertEquals(count($this->object),2);
    }

    /**
     * @covers Sdl\Parser\TokenStream::current
     * @todo   Implement testCurrent().
     */
    public function testCurrent()
    {
        $this->object->parseString("foo;");
        $this->assertEquals($this->object->current(),"foo");
    }

    /**
     * @covers Sdl\Parser\TokenStream::next
     * @todo   Implement testNext().
     */
    public function testNext()
    {
        $this->object->parseString("foo;");
        $this->object->next();
        $this->assertEquals($this->object->current(),";");
    }

    /**
     * @covers Sdl\Parser\TokenStream::key
     * @todo   Implement testKey().
     */
    public function testKey()
    {
        $this->object->parseString("foo;");
        $this->assertEquals($this->object->key(),0);
        $this->object->next();
        $this->assertEquals($this->object->key(),1);
    }

    /**
     * @covers Sdl\Parser\TokenStream::valid
     * @todo   Implement testValid().
     */
    public function testValid()
    {
        $this->object->parseString("foo;");
        $this->assertEquals($this->object->valid(),true);
        $this->object->next();
        $this->object->next();
        $this->object->next();
        $this->assertEquals($this->object->valid(),false);
    }

    /**
     * @covers Sdl\Parser\TokenStream::rewind
     * @todo   Implement testRewind().
     */
    public function testRewind()
    {
        $this->object->parseString("foo;");
        $this->object->next();
        $this->object->rewind();
        $this->assertEquals($this->object->key(),0);
    }

}

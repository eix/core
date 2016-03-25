<?php

namespace Eix\Core\Responses\Http\Media\Library;


class DomTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyConstructor() {
        $dom = new Dom;
        $nodes = $dom->getNodes('/');

        $this->assertEquals(1, $nodes->length);
        $this->assertFalse($nodes->item(0)->hasChildNodes());
    }

    public function testGetNodes() {
        $dom = new Dom('data/resources/test/dom/simple.xml');

        $this->assertEquals(2, $dom->getNodes('/root/child')->length);
    }

    public function testGetNode() {
        $dom = new Dom('data/resources/test/dom/simple.xml');

        $this->assertEquals('Test', trim($dom->getNode('/root/child[2]')->textContent));
    }
}

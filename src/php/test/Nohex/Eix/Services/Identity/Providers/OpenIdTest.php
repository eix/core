<?php
/**
 * Unit test for class Nohex\Eix\Services\Identity\Providers\OpenId.
 */

namespace Nohex\Eix\Services\Identity\Providers;

use Nohex\Eix\Services\Identity\Providers\OpenId;

class OpenIdTest extends \PHPUnit_Framework_TestCase
{
    const HOST = 'http://host';

    private $formerRequestUri;
    private $formerRequestMethod;

    public function setUp()
    {
        // Impersonate REQUEST_URI.
        $this->formerRequestUri = @$_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = 'https://this.is/a/request/uri';
        // Impersonate REQUEST_METHOD.
        $this->formerRequestMethod = @$_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    public function tearDown()
    {
        $_SERVER['REQUEST_URI'] = $this->formerRequestUri;
        $_SERVER['REQUEST_METHOD'] = $this->formerRequestMethod;
    }

    public function testDefaultConstructor()
    {
        $object = new OpenId(self::HOST);

        $this->assertTrue($object instanceof OpenId);
    }

}

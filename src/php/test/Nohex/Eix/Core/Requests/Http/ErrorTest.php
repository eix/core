<?php
/**
 * Unit test for class Nohex\Eix\Core\Requests\Http\Error.
 */

namespace Nohex\Eix\Core\Requests\Http;

use Nohex\Eix\Core\Requests\Http\Error as ErrorRequest;
use Nohex\Eix\Core\Requests\Exception;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    private $exception;

    protected function setUp()
    {
        $this->exception = new Exception('Test exception');
    }

    protected function tearDown()
    {
        unset($this->exception);
    }

    public function testDefaultConstructor()
    {
        $request = new ErrorRequest;

        $request->setException($this->exception);

        $this->assertEquals(
            $request->getException(),
            $this->exception
        );
    }

}

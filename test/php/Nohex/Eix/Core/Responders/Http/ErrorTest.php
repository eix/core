<?php
/**
 * Unit test for class Nohex\Eix\Core\Responders\Http\Error.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Core\Requests\Exception;
use Nohex\Eix\Core\Requests\Http as HttpRequest;
use Nohex\Eix\Core\Requests\Http\Error as ErrorRequest;
use Nohex\Eix\Core\Responders\Http\Error as ErrorResponder;

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
        $responder = new ErrorResponder;
        $responder->setException($this->exception);

        $this->assertEquals(
            $responder->getException(),
            $this->exception
        );
    }

    /**
     * @expectedException \Nohex\Eix\Core\Exception
     */
    public function testConstructorWithNormalRequest()
    {
        $request = new HttpRequest;
        $responder = new ErrorResponder($request);
    }

    /**
     * @expectedException \Nohex\Eix\Core\Exception
     */
    public function testConstructorWithEmptyErrorRequest()
    {
        $request = new ErrorRequest;
        $responder = new ErrorResponder($request);
    }

    public function testConstructorWithErrorRequest()
    {
        $request = new ErrorRequest;
        $request->setException(new \Exception('Test exception'));
        $responder = new ErrorResponder($request);
        $responder->setException($this->exception);

        $this->assertEquals(
            $responder->getException(),
            $this->exception
        );
    }

}

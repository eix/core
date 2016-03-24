<?php
/**
 * Unit test for class Eix\Core\Responders\Http\Error.
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Requests\Exception;
use Eix\Core\Requests\Http as HttpRequest;
use Eix\Core\Requests\Http\Error as ErrorRequest;
use Eix\Core\Responders\Http\Error as ErrorResponder;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDefaultConstructor()
    {
        new ErrorResponder;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithNormalRequest()
    {
        new ErrorResponder(new HttpRequest);
    }

    /**
     * @expectedException \Eix\Core\Exception
     */
    public function testConstructorWithEmptyErrorRequest()
    {
        new ErrorResponder(new ErrorRequest);
    }

    public function testConstructorWithException()
    {
        $exception = new Exception;
        $request = new ErrorRequest;
        $request->setThrowable(new \Exception('Test exception'));
        $responder = new ErrorResponder($request);
        $responder->setThrowable($exception);

        $this->assertEquals(
            $responder->getThrowable(),
            $exception
        );
    }

    public function testConstructorWithError()
    {
        $exception = new Exception;
        $request = new ErrorRequest;
        $request->setThrowable(new \Error('Test error'));
        $responder = new ErrorResponder($request);
        $responder->setThrowable($exception);

        $this->assertEquals(
            $responder->getThrowable(),
            $exception
        );
    }

}

<?php

namespace Eix\Core\Responders;

use Eix\Core\Request;
use Eix\Core\Requests\Http as HttpRequest;
use Eix\Services\Log\Logger;

/**
 * Factory for Responder classes.
 */
class Factory
{
    private function __construct()
    {
        // Prevent instatiation of this class.
    }

    private function __clone()
    {
        // Prevent cloning of this class.
    }

    /**
     * Returns a responder that can deal with the specified request.
     * @param  Request                  $request the request a responder is needed for.
     * @throws InvalidArgumentException
     */
    public static function getResponder(Request $request)
    {
        if ($request instanceof HttpRequest) {
            return self::getHttpResponder($request);
        } else {
            $requestType = get_class($request);
            throw new \InvalidArgumentException("Requests of type '{$requestType}' are not supported.");
        }
    }

    /**
     * Returns a responder that produces a response that informs about an error
     * condition.
     *
     * @param  Request                  $request the request a responder is needed for.
     * @throws InvalidArgumentException
     */
    public static function getErrorResponder(Request $request, \Exception $exception)
    {
        if ($request instanceof HttpRequest) {
            return self::getHttpErrorResponder($exception, $request);
        } else {
            $requestType = get_class($request);
            throw new \InvalidArgumentException("Requests of type '{$requestType}' are not supported.");
        }
    }

    /**
     * Returns a responder that supports HTTP requests.
     *
     * @param HttpRequest $request the request to satisfy.
     */
    private static function getHttpResponder(HttpRequest $request)
    {
        $responder = null;

        $responderClassName = $request->getResponderClassName();
        if (class_exists($responderClassName)) {
            $responder = new $responderClassName($request);
            Logger::get()->debug(
                "Responder '$responderClassName' is ready."
            );
        } else {
            throw new \Eix\Services\Net\Http\NotFoundException(
                "'$responderClassName' responder not found."
            );
        }

        return $responder;
    }

    /**
     * Returns a responder that deals with failed HTTP requests.
     *
     * @param \Throwable  $throwable the error the request has caused.
     * @param HttpRequest $request   the request that has not been
     * satisfied.
     */
    private static function getHttpErrorResponder(\Throwable $throwable, HttpRequest $request = null)
    {
        // If an error request has been made, oblige.
        $responder = new \Eix\Core\Responders\Http\Error($request);
        $responder->setThrowable($throwable);

        return $responder;
    }

}

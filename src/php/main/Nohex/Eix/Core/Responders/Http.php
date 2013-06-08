<?php

/**
 * Parent for HTTP responder classes.
 */

namespace Nohex\Eix\Core\Responders;

use Nohex\Eix\Core\Users;
use Nohex\Eix\Services\Log\Logger;

abstract class Http implements \Nohex\Eix\Core\Responder
{
    protected $response;
    protected $request;

    public function __construct($request = null)
    {
        if ($request) {
            if ($request instanceof \Nohex\Eix\Core\Requests\Http) {
                $this->request = $request;
            } else {
                throw new \InvalidArgumentException(
                    'An HTTP responder needs an HTTP request, '
                    . get_class($request)
                    . ' given.'
                );
            }
        }
    }

    /**
     * This function must be implemented with a function that will be called if
     * the only accepted content type that can be satisfied is * / *.
     *
     * E.g. the body for a responder that deals with web pages would be a call
     * to httpGetForHtml.
     */
    abstract protected function httpGetForAll();

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * The default behaviour consists of executing a function named after the
     * HTTP method and the eventual action contained in the request, to produce
     * a Response object.
     *
     * @return \Nohex\Eix\Core\Response
     * @throws \Nohex\Eix\Core\Exception
     */
    final public function getResponse()
    {
        if (empty($this->response)) {
            $httpMethod = $this->getRequest()
                ? strtoupper($this->getRequest()->getMethod())
                : 'GET'
            ;

            if ($this->isHttpMethodSupported($httpMethod)) {
                $functionName = $this->getFunctionName($httpMethod);
                Logger::get()->debug("Running responder method '$functionName'...");
                // If the responder is a restricted one...
                if ($this instanceof \Nohex\Eix\Core\Responders\Restricted) {
                    // ... ensure the user is allowed to use that function.
                    Users::getCurrent()->checkAuthorisation($this, $functionName);
                }

                // The response is the result of executing the appropriate
                // responder function.
                $this->response = $this->$functionName();
            } else {
                throw new \Nohex\Eix\Services\Net\Http\MethodNotAllowedException("This responder does not support {$httpMethod} requests.");
            }
        }

        return $this->response;
    }

    /**
     * Returns a function name that represents the method and response's
     * expected content type.
     *
     * e.g. httpGetForHtml represents a GET request expecting HTML back.
     * e.g. httpPostForJson represents a POST request expecting JSON back.
     * e.g. httpPutForAll represents a PUT request expecting whatever back.
     */
    private function getFunctionName($httpMethod)
    {
        if ($this->getRequest()) {
            $functionName = null;
            // Prefix starts with 'http'.
            $functionPrefix = 'http';
            // The HTTP method is then appended.
            $functionPrefix .= ucfirst(strtolower($httpMethod));
            // If an action has been captured in the URI, append it too.
            $action = $this->getRequest()->getParameter('action');
            if ($action) {
                $functionPrefix .= ucfirst($action);
                // If there is an action, check whether the function is likely
                // to exist.
                if (!$this->isBaseFunctionAvailable($functionPrefix)) {
                    throw new \Nohex\Eix\Services\Net\Http\NotFoundException(
                        "This responder does not have any methods starting with {$functionPrefix}."
                    );
                }
            }

            $found = false;
            $acceptedContentTypes = $this->getRequest()->getAcceptedContentTypes();
            // Append a token to the function name, based on the requested content
            foreach ($acceptedContentTypes as $contentType => $quality) {
                try {
                    $contentTypeToken = \Nohex\Eix\Core\Requests\Http::getContentTypeToken($contentType);
                    // Append the token for the response's expected content type.
                    $functionName = $functionPrefix . 'For' . $contentTypeToken;
                    Logger::get()->debug("Trying {$functionName} for {$contentType}...");
                    // Check whether a method with that name exists.
                    if (method_exists($this, $functionName)) {
                        return $functionName;
                    }
                } catch (\InvalidArgumentException $exception) {
                    // This content type is invalid, keep on checking.
                }
            }

            // Although a base method exists, it does not for this content type,
            // so that's a 406.
            throw new \Nohex\Eix\Services\Net\Http\NotAcceptableException(
                "This responder cannot serve content type {$contentType}."
            );
        } else {
            // If there is no request, return the generic responder method.
            return 'httpGetForAll';
        }
    }

    /**
     * Check whether there is at least one function that implements the
     * requested HTTP method.
     */
    private function isHttpMethodSupported($requestMethod)
    {
        $requestMethodName = 'http' . ucfirst(strtolower($requestMethod));
        foreach (get_class_methods($this) as $methodName) {
            if (strpos($methodName, $requestMethodName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether there is at least one function that implements the
     * requested HTTP method and an eventual action.
     */
    private function isBaseFunctionAvailable($functionName)
    {
        foreach (get_class_methods($this) as $responderMethodName) {
            if (strpos($responderMethodName, $functionName) === 0) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace Eix\Core\Responders\Http;

use Eix\Core\Exception;
use Eix\Core\Responders\Http as HttpResponder;
use Eix\Services\Log\Logger;

/**
 * Implements a responder that outputs error information in JSON.
 */
class Error extends HttpResponder
{
    private $exception;

    public function __construct($request = null)
    {
        parent::__construct($request);

        if ($request) {
            $exception = @$request->getException();
            if ($exception) {
                $this->setException($exception);
                Logger::get()->debug(sprintf(
                    'Error controller called because %s [%d].%s%s',
                    lcfirst($exception->getMessage()),
                    $exception->getCode(),
                    PHP_EOL,
                    $exception->getTraceAsString()
                ));
            } else {
                throw new Exception('The request does not carry an exception.');
            }
        }
    }
    protected function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    protected function httpGetForJson()
    {
        return $this->buildResponse('\Eix\Core\Responses\Http\Json');
    }

    protected function httpGetForXml()
    {
        return $this->buildResponse('\Eix\Core\Responses\Http\Xml');
    }

    protected function httpGetForHtml()
    {
        $response = $this->buildResponse('\Eix\Core\Responses\Http\Html');

        // The template is set according to the nature of the exception.
        $page = null;
        $code = $this->getException()->getCode();
        switch ($code) {
            case 404:
                $page = 'not_found';
                break;
            case 403:
            case 401:
                $page = 'security';
                break;
            default:
                $page = 'index';
                break;
        }

        $response->setStatus($code);
        $response->setTemplateId('.error/' . $page);

        return $response;
    }

    private function buildResponse($className)
    {
        $exception = $this->getException();
        $code = $exception->getCode();
        $response = new $className($this->getRequest());
        $response->setData('error', array(
            'code' => $code,
            'message' => $exception->getMessage()
        ));

        return $response;
    }

    public function setException($exception)
    {
        $this->exception = $exception;
    }

    public function getException()
    {
        if (!($this->exception) instanceof \Exception) {
            throw new \RuntimeException('This responder needs an exception.');
        }

        return $this->exception;
    }
}

<?php

namespace Eix\Core\Responders\Http;

use Eix\Core\Exception;
use Eix\Core\Responders\Http as HttpResponder;
use Eix\Services\Log\Logger;
use Eix\Core\Requests\Http\Error as ErrorRequest;

/**
 * Implements a responder that outputs error information in JSON.
 */
class Error extends HttpResponder
{
    /**
     * @var \Throwable
     */
    private $throwable;

    public function __construct($request = null)
    {
        if (!($request instanceof ErrorRequest)) {
            throw new \InvalidArgumentException(sprintf(
                "An error request is required for error responders: %s",
                get_class($request)
            ));
        }

        parent::__construct($request);

        if ($request) {
            $throwable = @$request->getThrowable();
            if ($throwable) {
                $this->setThrowable($throwable);
                Logger::get()->debug(sprintf(
                    'Error controller called because %s [%d].%s%s',
                    lcfirst($throwable->getMessage()),
                    $throwable->getCode(),
                    PHP_EOL,
                    $throwable->getTraceAsString()
                ));
            } else {
                throw new Exception('The request does not carry an error or an exception.');
            }
        }
    }
    protected function httpGetForAll()
    {
        return $this->httpGetForHtml();
    }

    protected function httpGetForJson()
    {
        return $this->buildResponse(\Eix\Core\Responses\Http\Json::class);
    }

    protected function httpGetForXml()
    {
        return $this->buildResponse(\Eix\Core\Responses\Http\Xml::class);
    }

    protected function httpGetForHtml()
    {
        $response = $this->buildResponse(\Eix\Core\Responses\Http\Html::class);

        // The template is set according to the nature of the throwable object.
        $page = null;
        $code = $this->getThrowable()->getCode();
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
        $throwable = $this->getThrowable();
        $code = $throwable->getCode();
        $response = new $className($this->getRequest());
        $response->setData('error', array(
            'code' => $code,
            'message' => $throwable->getMessage()
        ));

        return $response;
    }

    public function setThrowable(\Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    public function getThrowable()
    {
        return $this->throwable;
    }
}

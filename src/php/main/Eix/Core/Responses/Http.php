<?php

/**
 * Provides a response to an HTTP request.
 */

namespace Eix\Core\Responses;

use Eix\Services\Log\Logger;
use Eix\Core\Responses\Data as DataResponse;
use Eix\Core\Requests\Http as HttpRequest;
use Eix\Services\Net\Http as HttpClient;

abstract class Http extends DataResponse
{
    const STATUS_NOTICE = 'notice';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    protected $status = HttpClient::STATUS_OK;
    private $request;
    private $contentType;
    private $encoding = 'UTF-8';
    private $headers = array();

    public function __construct(HttpRequest $request = null)
    {
        Logger::get()->debug('Using HTTP response ' . get_class($this));

        $this->request = $request;
    }

    /**
     * The default output of an HTTP response is composed of the headers.
     */
    public function issue()
    {
        // If there is no next URL, just output the headers as expected.
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}", true);
        }

        // Output content type.
        if ($this->contentType) {
            header("content-type: {$this->contentType}; charset={$this->encoding}");
        }

        // Output status code.
        $statusMessage = sprintf('%d %s',
            $this->status,
            HttpClient::getStatusCodeMessage($this->status)
        );
        header('Status: ' . $statusMessage);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Add an HTTP header to the output.
     * @param string $key   the header name.
     * @param string $value the header value.
     */
    protected function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Sets the status, if it is a valid HTTP status code.
     *
     * @param  int     $status an integer representing a valid HTTP status code.
     * @return boolean whether the status was set or not.
     */
    public function setStatus($status)
    {
        $result = \Eix\Services\Net\Http::isStatusCodeValid($status);
        if ($result) {
            $this->status = $status;
        }

        return $result;
    }

    /**
     * Adds informative status messages to the response data.
     *
     * @param string $messages
     */
    public function addNotice($messages)
    {
        $this->addStatusMessage(self::STATUS_NOTICE, $messages);
    }

    /**
     * Adds warning status messages to the response data.
     *
     * @param string $messages
     */
    public function addWarning($messages)
    {
        $this->addStatusMessage(self::STATUS_WARNING, $messages);
    }

    /**
     * Adds error status messages to the response data.
     *
     * @param string $messages
     */
    public function addErrorMessage($messages)
    {
        $this->addStatusMessage(self::STATUS_ERROR, $messages);
    }

    /**
     * Adds one or more status messages to the response data.
     *
     * @param string $messages
     */
    protected function addStatusMessage($type, $messages)
    {
        if (!is_array($messages)) {
            $messages = array($messages);
        }
        $this->addData('status', array($type => $messages));
    }

}

<?php

namespace Eix\Core\Requests\Http;

/**
 * Encapsulates a request to generate a response for a failure condition.
 */
class Error extends \Eix\Core\Requests\Http
{
    // The object that describes the error situation.
    private $throwable;

    /**
     * Builds a request that describes an error condition, to be used when a
     * request could not be created.
     */
    public function __construct()
    {
        parent::__construct();

        // Method is always GET.
        $this->method = self::HTTP_METHOD_GET;

        // Cancel the URI parsing.
        $this->uri = false;
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

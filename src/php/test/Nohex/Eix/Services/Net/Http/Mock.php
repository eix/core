<?php

namespace Nohex\Eix\Services\Net\Http;

use Nohex\Eix\Services\Net\Http;

/**
 * Mock HTTP client for unit testing.
 */
class Mock extends Http
{

    /**
     * Performs the actual request.
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $parameters
     * @return string
     */
    protected function request($method, $url, $parameters = array())
    {
        $output = sprintf('%s %s', $method, $url);
        $errNo = @$parameters['errNo'];
        $status = @$parameters['status'];

        if ($errNo) {
            throw new Exception('HTTP: cURL failed: ' . $error, $errNo);
        }

        // If cURL had no errors itself, then there is an HTTP response.
        switch ($status) {
            case self::STATUS_OK:
            case self::STATUS_CREATED:
            case self::STATUS_ACCEPTED:
            case self::STATUS_NO_CONTENT:
                return $output;
            default:
                throw new Http\Exception("HTTP {$status}", $status);
        }
    }
}

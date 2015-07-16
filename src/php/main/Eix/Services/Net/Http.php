<?php

namespace Eix\Services\Net;

/**
 * HTTP client.
 */
class Http
{
    const DEFAULT_PORT = 80;
    const DEFAULT_PROTOCOL = 'http';
    const DEFAULT_CONTENT_TYPE = 'application/json';

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_NO_CONTENT = 204;
    const STATUS_SEE_OTHER = 303;

    private $protocol;
    private $host;
    private $port;
    private $headers = array();
    private $user;
    private $password;
    private $contentType = self::DEFAULT_CONTENT_TYPE;
    private $acceptedContentType = self::DEFAULT_CONTENT_TYPE;

    /**
     * This HTTP status codes list is purportedly complete. Since not all codes
     * apply to a system such as Eix in its current incarnation, they have been
     * commented out.
     * @var type
     */
    private static $statusCodes = array(
        self::STATUS_OK => 'OK',
        self::STATUS_CREATED => 'Created',
        self::STATUS_ACCEPTED => 'Accepted',
//		203 => 'Non-authoritative information',
        self::STATUS_NO_CONTENT => 'No content',
//		205 => 'Reset content',
//		206 => 'Partial content',
//		207 => 'Multi-status',
//		208 => 'Already reported',
//		226 => 'IM used',
//		300 => 'Multiple choices',
        301 => 'Moved permanently',
        302 => 'Found',
        self::STATUS_SEE_OTHER => 'See other',
//		304 => 'Not modified',
//		305 => 'Use proxy',
//		306 => 'Switch proxy',
//		307 => 'Temporary redirect',
//		308 => 'Resume incomplete',

        400 => 'Bad request',
        401 => 'Unauthorized',
//		402 => 'Payment required',
        403 => 'Forbidden',
        404 => 'Not found',
        405 => 'Method not allowed',
        406 => 'Not acceptable',
//		407 => 'Proxy authentication required',
        408 => 'Request timeout',
//		409 => 'Conflict',
//		410 => 'Gone',
//		411 => 'Length required',
//		412 => 'Precondition failed',
//		413 => 'Request entity too large',
//		414 => 'Request URI too long',
//		415 => 'Unsupported media type',
//		416 => 'Requested range not satisfiable',
//		417 => 'Expectaction failed',
//		422 => 'Unprocessable entity',
//		423 => 'Locked',
//		424 => 'Failed dependency',
//		425 => 'Unordered collection',
//		426 => 'Upgrade required',
//		428 => 'Precondition required',
//		429 => 'Too many requests',
//		431 => 'Request header fields too large',

        500 => 'Internal server error',
//		501 => 'Not implemented',
//		502 => 'Bad gateway',
//		503 => 'Service unavailable',
//		504 => 'Gateway timeout',
//		505 => 'HTTP version not supported',
//		506 => 'Variant also negotiates',
//		507 => 'Insufficient storage',
//		508 => 'Loop detected',
//		510 => 'Not extended',
//		511 => 'Network authentication required'
    );

    public function __construct($settings)
    {
        $this->host = $settings->host;
        $this->protocol = @$settings->protocol ?: self::DEFAULT_PROTOCOL;
        $this->port = @$settings->port ?: self::DEFAULT_PORT;
    }

    /**
     * setHeaders
     *
     * @param  array $headers
     * @return Http
     */
    public function setHeader($key, $value)
    {
        $this->_headers[$key] = $value;

        // Allows chaining.
        return $this;
    }

    /**
     * GET request
     *
     * @param  string $uri
     * @param  array  $parameters
     * @return string
     */
    public function get($uri, $parameters = array())
    {
        return $this->request(self::METHOD_GET, $this->getUrl($uri), $parameters);
    }

    /**
     * POST request
     *
     * @param  string $uri
     * @param  array  $parameters
     * @return string
     */
    public function post($uri, $parameters = array())
    {
        return $this->request(self::METHOD_POST, $this->getUrl($uri), $parameters);
    }

    /**
     * DELETE request
     *
     * @param  string $uri
     * @param  array  $parameters
     * @return string
     */
    public function delete($uri, $parameters = array())
    {
        return $this->request(self::DELETE, $this->getUrl($uri), $parameters);
    }

    private function getUrl($uri)
    {
        // Remove leading slash, it's already present in the next step.
        if (strpos($uri, '/') === 0) {
            $uri = substr($uri, 1);
        }

        return sprintf('%s://%s:%d/%s',
            $this->protocol,
            $this->host,
            $this->port,
            $uri
        );
    }

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
        Logger::get()->debug('HTTP: starting request...');
        $headers = $this->headers;

        // Add accepted content type header.
        $headers[] = 'Content-Type: ' . $this->contentType;
        $headers[] = 'Accept: ' . $this->acceptedContentType;

        $handler = curl_init();

        if (!is_null($this->user)) {
            curl_setopt($handler, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        }

        Logger::get()->debug('HTTP: method is ' . $method);
        switch ($method) {
            case self::METHOD_DELETE:
                curl_setopt($handler, CURLOPT_URL, $url . '?' . http_build_query($parameters));
                curl_setopt($handler, CURLOPT_CUSTOMREQUEST, self::DELETE);
                break;
            case self::METHOD_POST:
                curl_setopt($handler, CURLOPT_URL, $url);
                curl_setopt($handler, CURLOPT_POST, true);
                curl_setopt($handler, CURLOPT_POSTFIELDS, $parameters);
                break;
            case self::METHOD_GET:
                curl_setopt($handler, CURLOPT_URL, $url . '?' . http_build_query($parameters));
                break;
        }

        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        Logger::get()->debug('HTTP: headers set.');

        // Send the request to the server.
        Logger::get()->debug("HTTP: requesting $url...");
        $output = curl_exec($handler);
        $errNo = curl_errno($handler);
        $error = curl_error($handler);
        // Obtain the status.
        Logger::get()->debug('HTTP: Done. Getting status...');
        $status = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        // The handler is no longer needed.
        curl_close($handler);
        Logger::get()->debug('HTTP: request finished.');

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

    /**
     * Asserts whether the number in $statusCode is a recognised HTTP status
     * code.
     * @param  integer $statusCode the code to check.
     * @return boolean
     */
    public static function isStatusCodeValid($statusCode)
    {
        return array_key_exists($statusCode, self::$statusCodes);
    }

    /**
     * Returns the message that matches an HTTP status code.
     * @param  integer $statusCode the code the message is needed for.
     * @return string  the HTTP status message.
     */
    public static function getStatusCodeMessage($statusCode)
    {
        return @self::$statusCodes[$statusCode];
    }
}

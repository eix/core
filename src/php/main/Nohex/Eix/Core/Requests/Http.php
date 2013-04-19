<?php

namespace Nohex\Eix\Core\Requests;

use Nohex\Eix\Core\Application;
use Nohex\Eix\Services\Log\Logger;

/**
 * Objectifies the current HTTP request information.
 */
class Http implements \Nohex\Eix\Core\Request
{
    const HTTP_METHOD_GET = 'GET';

    const ROUTES_FILE_LOCATION = '../data/environment/routes.json';
    const DEFAULT_CONTENT_TYPE = 'text/html';

    protected $uri = "";
    protected $method;

    private $acceptedContentTypes;
    private $parameters = null;
    private $httpParameters = array();

    private static $routes;

    public function __construct()
    {
        // Constructor left here so that it can be overriden.
    }

    /**
     * Extracts the responder, action and parameter from an HTTP request.
     */
    private function parsePath()
    {
        $components = array();
        $uri = $this->getUri();

        if ($uri !== false) {
            Logger::get()->debug('Parsing URI: ' . $uri);
            // Add the URL parameters.
            $components = $this->getHttpParameters();

            $matched = false;
            foreach (self::getRoutes() as $route) {
                if (preg_match("#^{$route['uri']}$#", $uri, $matches)) {
                    Logger::get()->debug('Found a matching route: ' . $route['uri']);
                    foreach ($route as $name => $value) {
                        switch ($name) {
                            case 'uri':
                                // Ignore.
                                break;
                            default:
                                if (is_numeric($value)) {
                                    // If the value is a number, it refers to a
                                    // regex block in the route.
                                    if (isset($matches[$value])) {
                                        $components[$name] = $matches[$value];
                                    }
                                } else {
                                    // Otherwise it's the parameter's value.
                                    $components[$name] = $value;
                                }
                                break;
                        }
                    }

                    $matched = true;
                    break;
                }
            }

            // If no matching routes have been found, the request is not
            // considered valid.
            if (!$matched) {
                throw new Exception("No routes match URI '{$uri}'.", 404);
            }
        }

        return $components;
    }

    /**
     * Reads the Accepted header in the request, and returns a list of content
     * types sorted by preference.
     */
    public function getAcceptedContentTypes()
    {
        if (empty($this->acceptedContentTypes)) {
            // Values will be stored in this array
            $this->acceptedContentTypes = array();

            // Accept header is case insensitive, and whitespace isn’t important.
            $acceptHeader = strtolower(str_replace(' ', '', @$_SERVER['HTTP_ACCEPT'] ?: '*/*'));
            // Extract the content types from the header.
            $acceptHeaderItems = explode(',', $acceptHeader);
            foreach ($acceptHeaderItems as $contentType) {
                // The default quality is 1.
                $quality = 1;
                // Check whether a quality is specified.
                if (strpos($contentType, ';q=')) {
                    list($contentType, $quality) = explode(';q=', $contentType);
                }

                $this->acceptedContentTypes[$contentType] = $quality;
            }
            // Sort the content types by relevance.
            arsort($this->acceptedContentTypes);
        }

        return $this->acceptedContentTypes;
    }

    /**
     * Finds out which locale the request would want for the response.
     */
    public function getLocale()
    {
        // Use the intl extension if present.
        if (class_exists('\Locale')) {
            $locale = \Locale::acceptFromHttp(@$_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        // If no locale was obtained, resort to the default one.
        if (empty($locale)) {
            $locale = Application::getSettings()->locale->default;
        }

        return $locale;
    }

    /**
     * Returns an array with all the request's parameters.
     */
    public function getParameters()
    {
        // If the parameters property is null, it still hasn't been set. Parse
        // the path components first.
        if (is_null($this->parameters)) {
            $this->parameters = $this->parsePath();
        }

        return $this->parameters;
    }

    /**
     * Returns a specific parameter, or null if it's not found.
     */
    public function getParameter($name)
    {
        $parameters = $this->getParameters();

        return isset($parameters[$name])
            ? $parameters[$name]
            : null
        ;
    }

    /**
     * Returns the HTTP method used in the request.
     */
    public function getMethod()
    {
        if (empty($this->method)) {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }

        return strtoupper($this->method);
    }

    /**
     * Returns the fragment of the responder's namespace.
     */
    public function getResponderClassName()
    {
        return $this->getParameter('responder');
    }

    /**
     * Returns the current request's URI, finding it out if needed.
     *
     * @return string
     */
    private function getUri()
    {
        if (empty($this->uri) && ($this->uri !== false)) {
            $this->uri = $_SERVER['REQUEST_URI'];

            // Remove the parameters from the URI.
            $paramsStart = strpos($this->uri, '?');
            if ($paramsStart !== false) {
                $this->uri = substr($this->uri, 0, $paramsStart);
            }
        }

        return $this->uri;
    }

    /**
     * Returns the parameters that came in the HTTP request.
     *
     * @return type
     */
    private function getHttpParameters()
    {
        if (empty($this->httpParameters)) {
            // Discover the parameters from the server data.
            $uri = $_SERVER['REQUEST_URI'];
            $paramsStart = strpos($uri, '?');
            if ($paramsStart !== false) {
                // Separate the parameters in the URL.
                parse_str(substr($uri, $paramsStart + 1), $this->httpParameters);
            }

            // Get POST parameters as well.
            $this->httpParameters = array_merge($this->httpParameters, $_POST);

            // Get files too.
            $this->httpParameters = array_merge($this->httpParameters, $_FILES);
        }

        return $this->httpParameters;
    }

    /**
     * Returns the URL-Responder map.
     *
     * @throws Exception if no usable routes source is available.
     */
    public static function getRoutes()
    {
        if (empty(self::$routes)) {
            // If no routes have been set, try the standard file.
            if (is_readable(self::ROUTES_FILE_LOCATION)) {
                // Decode the JSON routes file into an array.
                self::$routes = json_decode(
                    file_get_contents(self::ROUTES_FILE_LOCATION),
                    true
                );
            }
            // No valid routes have been found.
            if (empty(self::$routes)) {
                throw new Exception('No routes could be obtained. Please check the route configuration file.');
            }
        }

        return self::$routes;
    }

    /**
     * Set the routing table.
     */
    public static function setRoutes($routes)
    {
        self::$routes = $routes;
    }

    /**
     * Returns the URL address previous to the current one.
     *
     * @return string
     */
    public function getReferrer()
    {
        return @$_SERVER['HTTP_REFERER'];
    }

    /**
     * Produces a token that uniquely identifies a content type and can be used
     * as part of a method name.
     *
     * @param string $contentType the content type to get a token for.
     * @param string $default     a default content type
     */
    public static function getContentTypeToken($contentType)
    {
        $token = null;
        switch ($contentType) {
            case 'text/html':
                $token = 'Html';
                break;
            /*
            case 'application/xhtml+xml':
                $token = 'Xhtml';
                break;
                */
            case 'application/json':
            case 'text/json':
                $token = 'Json';
                break;
            case 'application/xml':
            case 'text/xml':
                $token = 'Xml';
                break;
            case 'image/png':
            case 'image/jpeg':
            case 'image/*':
                $token = 'Image';
                break;
            case '*/*':
                $token = 'All';
                break;
            default:
                throw new \InvalidArgumentException("Content type '{$contentType}' is not supported.");
        }

        return $token;
    }

    public function getCurrentUrl()
    {
        return $_SERVER['REQUEST_URI'];
    }
}

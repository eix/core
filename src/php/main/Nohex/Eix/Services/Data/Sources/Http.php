<?php

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Services\Data\Source as DataSource;
use Nohex\Eix\Core\Application;
use Nohex\Eix\Services\Net\Http as HttpClient;

/**
 * Provides access to data sources through HTTP.
 */
class Http implements DataSource
{
    private static $settings;
    private static $httpClient;

    public function __construct($settings = null)
    {
        if (empty($settings)) {
            $settings = Application::getSettings()->data->sources->http;
        }

        self::$settings = $settings;
    }

    public static function setHttpClient(HttpClient $httpClient)
    {
        self::$httpClient = $httpClient;
    }

    public static function getHttpClient()
    {
        if (empty(self::$httpClient)) {
            self::$httpClient = new HttpClient(self::$settings);
        }

        return self::$httpClient;
    }

    private function getUri(array $parameters)
    {
        $uri = @$parameters['uri'];
        if ($uri === false) {
            throw new Exception('No URI was set.');
        }
    }

    public function create(array $data)
    {
        $uri = $this->getUri($data);

        return $this->httpClient()->post($uri, $data);
    }

    public function retrieve($id)
    {
        $uri = $this->getUri($id);

        return $this->httpClient()->get($uri);
    }

    public function retrieveAll(array $filter = null, array $fields = null)
    {
        throw \LogicException('retrieveAll not implemented!');
    }

    public function update($id, array $data)
    {
        $uri = $this->getUri($data);

        return $this->httpClient()->put($uri, $data);
    }

    public function destroy($id)
    {
        $uri = $this->getUri($id);

        return $this->httpClient()->delete($uri);
    }
}

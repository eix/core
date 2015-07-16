<?php

namespace Eix\Core\Requests\Factories;

use Eix\Core\Application;
use Eix\Core\Requests\Http as HttpRequest;
use Eix\Core\Requests\Factory as RequestFactory;

/**
 * The Responders class is a factory for Responder classes, which take a
 * request and are able to produce a fitting response.
 */
class Http extends RequestFactory
{
    /**
     * Chooses a responder that can satisfy a HTTP request.
     *
     * @param \Eix\Core\Request $request the request to satisfy.
     */
    public function get(Application $application)
    {
        $request = new HttpRequest($application);

        return $request;
    }
}

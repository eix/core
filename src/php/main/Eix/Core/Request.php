<?php

namespace Eix\Core;

/**
 * Defines a request an Eix application can use.
 * Eix requests produce a responder, an action, and some parameters that the
 * action may need, to be used so:
 * new $responder->$action($parameters);
 */
interface Request
{
    /**
     * Returns the path to a responder class that can parse the current request.
     */
    public function getResponderClassName();

    /**
     * Returns the parameters associated with the request.
     */
    public function getParameters();
}

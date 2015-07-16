<?php

namespace Eix\Core\Requests;

use Eix\Core\Application;

/**
 * Factory for Request classes.
 */
abstract class Factory
{
    /**
     * Returns a Request object that represents a request.
     *
     * @return \Eix\Core\Request
     */
    abstract public function get(Application $application);

}

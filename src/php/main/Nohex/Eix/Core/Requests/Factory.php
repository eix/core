<?php

namespace Nohex\Eix\Core\Requests;

use Nohex\Eix\Core\Application;

/**
 * Factory for Request classes.
 */
abstract class Factory
{
    /**
     * Returns a Request object that represents a request.
     *
     * @return \Nohex\Eix\Core\Request
     */
    abstract public function get(Application $application);

}

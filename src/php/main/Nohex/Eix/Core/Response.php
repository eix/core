<?php
/**
 * Provides a mechanism for the application to react to a request.
 */

namespace Nohex\Eix\Core;

interface Response
{
    /**
     * Performs the action that satisfies the request.
     */
    public function issue();
}

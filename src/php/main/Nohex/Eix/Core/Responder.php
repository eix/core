<?php

namespace Nohex\Eix\Core;

/**
 * A responder is an object that takes a particular request, works out which
 * response will satisfy it, and returns an instance of the latter.
 */
interface Responder
{
    public function getRequest();

    /**
     * Obtains a response from the responder. This function needs to be
     * idempotent to avoid running the processes to obtain a response more than
     * once.
     */
    public function getResponse();
}

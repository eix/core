<?php

namespace Eix\Core\Responses\Http;

use Eix\Core\Responses\Http as HttpResponse;

/**
 * Provides a fixed response for testing purposes.
 */
class Mock extends HttpResponse
{
    public function issue()
    {
        return $this->data;
    }
}

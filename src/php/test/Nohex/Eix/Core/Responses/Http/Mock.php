<?php

namespace Nohex\Eix\Core\Responses\Http;

use Nohex\Eix\Core\Responses\Http as HttpResponse;

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

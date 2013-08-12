<?php

namespace Nohex\Eix\Services\Net\Http;

class NotAuthenticatedException extends Exception
{
    protected $code = 403;

}

<?php

namespace Nohex\Eix\Services\Net\Http;

class MethodNotAllowedException extends Exception
{
    protected $code = 405;

}

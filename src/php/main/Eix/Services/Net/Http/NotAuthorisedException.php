<?php

namespace Eix\Services\Net\Http;

class NotAuthorisedException extends Exception
{
    protected $code = 401;

}

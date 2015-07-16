<?php

namespace Eix\Services\Identity;

/**
 * Describes an identity provider, which is a class used to provide users of
 * the system with services such as authentication.
 */
interface Provider
{
    public function authenticate();
}

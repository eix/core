<?php

namespace Nohex\Eix\Services\Data\Validators;

use Nohex\Eix\Services\Data\Validator;

/**
 * Asserts that the value is a valid e-mail address.
 */
class EmailValidator implements Validator
{
    public function isValid($value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function getFailMessage()
    {
        return _('The e-mail address is not valid.');
    }
}

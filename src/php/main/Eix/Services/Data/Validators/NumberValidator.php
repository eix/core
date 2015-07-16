<?php

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validator;

/**
 * Asserts that the value is a number.
 */
class NumberValidator implements Validator
{
    public function isValid($value)
    {
        return is_numeric($value);
    }

    public function getFailMessage()
    {
        return _('The value must be a number.');
    }
}

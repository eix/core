<?php

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validator;

/**
 * Asserts that the value is not empty.
 */
class NonEmptyValidator implements Validator
{
    public function isValid($value)
    {
        return (
            // Asserts that $value is not null.
            !is_null($value)
            &&
            // Asserts that, if $value is a string, it is not empty.
            !(is_string($value) && ($value === ''))
            &&
            !(
                is_array($value)
                && (
                    // Asserts that, if $value is an array, it is not an empty one.
                    ($value === array())
                    &&
                    // Asserts that, if $value is a file, it is not an empty one.
                    !(isset($value['tmp_file']) && (@$value['error'] == UPLOAD_ERR_NO_FILE))
                )
            )
        );
    }

    public function getFailMessage()
    {
        return _('This field cannot be empty.');
    }
}

<?php

namespace Eix\Services\Data;

/**
 * Defines a template for classes which can ascertain whether a value conforms
 * to an arbitrary set of rules.
 */
interface Validator
{
    /**
     * @return boolean whether the value is valid.
     */
    public function isValid($value);

    /**
     * @return string provides a message that, in the case of a failed
     * validation, informs about what went wrong.
     */
    public function getFailMessage();
}

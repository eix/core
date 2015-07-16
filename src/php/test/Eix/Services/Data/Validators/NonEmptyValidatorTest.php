<?php
/**
 * Unit test for class Eix\Services\Data\Validators\NonEmptyValidator.
 */

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validators\NonEmptyValidator;

class NonEmptyValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyString()
    {
        $validator = new NonEmptyValidator;

        $this->assertFalse(
            $validator->isValid(''),
            $validator->getFailMessage()
        );
    }

    public function testZero()
    {
        $validator = new NonEmptyValidator;

        $this->assertTrue(
            $validator->isValid(0),
            $validator->getFailMessage()
        );
    }

    public function testStringWithZero()
    {
        $validator = new NonEmptyValidator;

        $this->assertTrue(
            $validator->isValid('0'),
            $validator->getFailMessage()
        );
    }

    public function testFalse()
    {
        $validator = new NonEmptyValidator;

        $this->assertTrue(
            $validator->isValid(false),
            $validator->getFailMessage()
        );
    }

    public function testEmptyArray()
    {
        $validator = new NonEmptyValidator;

        $this->assertFalse(
            $validator->isValid(array()),
            $validator->getFailMessage()
        );
    }

}

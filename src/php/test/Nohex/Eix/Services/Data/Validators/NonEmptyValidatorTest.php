<?php
/**
 * Unit test for class Nohex\Eix\Services\Data\Validators\NonEmptyValidator.
 */

namespace Nohex\Eix\Services\Data\Validators;

use Nohex\Eix\Services\Data\Validators\NonEmptyValidator;

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

<?php
/**
 * Unit test for class Eix\Services\Data\Validators\NumberValidator.
 */

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validators\NumberValidator;

class NumberValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testNumber()
    {
        $validator = new NumberValidator;

        $this->assertTrue(
            $validator->isValid(12345),
            $validator->getFailMessage()
        );
    }

    public function testFloat()
    {
        $validator = new NumberValidator;

        $this->assertTrue(
            $validator->isValid(123.45),
            $validator->getFailMessage()
        );
    }

    public function testNumberInAString()
    {
        $validator = new NumberValidator;

        $this->assertTrue(
            $validator->isValid('12345'),
            $validator->getFailMessage()
        );
    }

    public function testFloatInAString()
    {
        $validator = new NumberValidator;

        $this->assertTrue(
            $validator->isValid('123.45'),
            $validator->getFailMessage()
        );
    }

    public function testSpelledOutNumber()
    {
        $validator = new NumberValidator;

        $this->assertFalse(
            $validator->isValid('one'),
            $validator->getFailMessage()
        );
    }

    public function testNonNumericString()
    {
        $validator = new NumberValidator;

        $this->assertFalse(
            $validator->isValid('first'),
            $validator->getFailMessage()
        );
    }

}

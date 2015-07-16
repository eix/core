<?php
/**
 * Unit test for class Eix\Services\Data\Validators\ImageValidator.
 */

namespace Eix\Services\Data\Validators;

use Eix\Services\Data\Validators\PostedImageValidator;

class PostedImageValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $fileLocation;

    public function setUp()
    {
        // Create a temporary test file.
        $this->fileLocation = tempnam(sys_get_temp_dir(), 'eix-test-');
        // Write something into it.
        file_put_contents($this->fileLocation, 'Eix test. Please delete me.');
    }

    public function tearDown()
    {
        // Delete the temporary test file.
        @unlink($this->fileLocation);
    }

    public function testIsValid()
    {
        $validator = new PostedImageValidator;

        $fileData = array(
            'tmp_name' => $this->fileLocation,
            'type' => 'image/png',
        );

        $this->assertTrue(
            $validator->isValid($fileData),
            $validator->getFailMessage()
        );
    }

    public function testEmptyFile()
    {
        $validator = new PostedImageValidator;

        $fileData = array();

        $this->assertFalse(
            $validator->isValid($fileData),
            $validator->getFailMessage()
        );
    }

    public function testWrongImageFormat()
    {
        $validator = new PostedImageValidator;

        $fileData = array(
            'tmp_name' => $this->fileLocation,
            'type' => 'text/javascript',
        );

        $this->assertFalse(
            $validator->isValid($fileData),
            $validator->getFailMessage()
        );
    }

}

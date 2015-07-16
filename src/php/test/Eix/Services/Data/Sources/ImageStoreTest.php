<?php
/**
 * Unit test for class Eix\Services\Data\Sources\ImageStore.
 */

namespace Eix\Services\Data\Sources;

use Eix\Core\MockApplication;
use Eix\Services\Data\Sources\ImageStore;

class ImageStoreTest extends \PHPUnit_Framework_TestCase
{
    const COLLECTION = 'test';

    private $application;

    protected function setUp()
    {
        $this->application = new MockApplication;
    }

    protected function tearDown()
    {
        $this->application = null;
    }

    public function testSingleton()
    {
        $dataSource = ImageStore::getInstance(self::COLLECTION);

        $this->assertTrue($dataSource instanceof ImageStore);
    }

}

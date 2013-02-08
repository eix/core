<?php
/**
 * Unit test for class Nohex\Eix\Services\Data\Sources\ImageStore.
 */

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Services\Data\Sources\ImageStore;

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

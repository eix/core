<?php
/**
 * Unit test for class Nohex\Eix\Services\Data\Sources\MongoDB.
 */

namespace Nohex\Eix\Services\Data\Sources;

use Nohex\Eix\Core\MockApplication;
use Nohex\Eix\Services\Data\Sources\MongoDB;
use Nohex\Eix\Services\Data\Sources\Connectors\MockMongo;

class MongoDBTest extends \PHPUnit_Framework_TestCase
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

    public function testConstructor()
    {
        MongoDB::setConnector(new MockMongo);
        $this->assertTrue(MongoDB::getConnector() instanceof MockMongo);

        $dataSource1 = MongoDB::getInstance(self::COLLECTION);
        $dataSource2 = MongoDB::getInstance(self::COLLECTION);

        $this->assertSame($dataSource1, $dataSource2);
    }

    // public function testConvertFromMongo() {
    // 	$sourceData = array(
    // 		'id' => 'test_data_id'
    // 	);

    // 	$targetData = MongoDB::convertFromMongo($sourceData);
    // 	$expectedData = $sourceData;

    // 	$this->assertEquals($targetData, $expectedData);
    // }

}

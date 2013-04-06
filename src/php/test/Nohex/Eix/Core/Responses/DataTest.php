<?php

namespace Nohex\Eix\Core\Responses;

use Nohex\Eix\Core\Responses\Mock as MockResponse;

class DataTest extends \PHPUnit_Framework_TestCase
{
	private $response;

	public function setUp() {
		$this->response = new MockResponse();
		$this->response->setData('key', 'value');
	}

	public function tearDown() {
		$this->response = null;
	}

    public function testGetData()
    {
		$data = $this->response->getData();
		$this->assertEquals($data['key'], 'value');
    }

    public function testSetData()
    {
    	$this->response->setData('another', 'piece of data');
		$data = $this->response->getData();
		$this->assertEquals($data['key'], 'value');
		$this->assertEquals($data['another'], 'piece of data');
    }
}

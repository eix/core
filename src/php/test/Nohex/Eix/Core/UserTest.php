<?php
/**
 * Unit test for class Nohex\Eix\Core\User.
 */

namespace Nohex\Eix\Core;

use Nohex\Eix\Core\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    private static $data;

    protected function setUp()
    {
        self::$data = array(
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'permissions' => array('*'),
        );
    }

    public function testDefaultConstructor()
    {
        $user = new User(self::$data);
        $publicData = $user->getPublicData();

        $this->assertEquals($publicData['name'], self::$data['name']);
        $this->assertEquals($publicData['email'], self::$data['email']);
    }

    protected function tearDown()
    {
        self::$data = null;
    }

}

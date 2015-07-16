<?php
/**
 * Unit test for class Eix\Services\Net\Socket.
 */

namespace Eix\Services\Net;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    private $port;
    private $socket;

    protected function setUp()
    {
        $this->startSocketServer();
    }

    protected function tearDown()
    {
        $this->stopSocketServer();
    }

    public function testDefaultConstructor()
    {
        // Requires a running web server.
        $socket = new Socket('tcp', 'localhost', $this->port, 5);

        $this->assertTrue($socket instanceof Socket);
    }

    private function startSocketServer()
    {
        // Set a random upper port for the test socket.
        $this->port = rand(1024, 32767);
        $this->socket = null;
        $tries = 0;
        // Try to open a listening socket
        while (!$this->socket) {
            // Try up to five different random ports. If, against all odds,
            // none are free, abandon.
            if ($tries > 5) {
                throw new Exception('Could not connect to any socket');
            } else {
                $tries++;
            }

            $this->socket = @socket_create_listen($this->port);
        }
    }

    private function stopSocketServer()
    {
        @socket_close($this->socket);
    }
}

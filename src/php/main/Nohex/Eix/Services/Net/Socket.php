<?php

namespace Nohex\Eix\Services\Net;

use Nohex\Eix\Services\Log\Logger;

/**
 * Provides a wrapper for socket-related operations.
 */
class Socket
{
    private $protocol;
    private $host;
    private $port;
    private $timeout;
    private $socket;
    private $bufferSize;

    /**
     * Creates the object that will deal with the socket.
     */
    public function __construct($protocol = 'tcp', $host = null, $port = null, $timeout = 30)
    {
        $this->setProtocol($protocol);
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        // Create the socket.
        $this->socket = socket_create(AF_INET, SOCK_STREAM, $this->protocol);
        // Set receive timeout.
        socket_set_option($this->socket,
            SOL_SOCKET,
            SO_RCVTIMEO,
            array(
                'sec' => $this->timeout,
                'usec' => 0,
            )
        );
        // Set send timeout.
        socket_set_option($this->socket,
            SOL_SOCKET,
            SO_SNDTIMEO,
            array(
                'sec' => $this->timeout,
                'usec' => 0,
            )
        );

        if ($this->socket) {
            $this->bufferSize = socket_get_option($this->socket, SOL_SOCKET, SO_RCVBUF);
            // If host and port are specified, connect automatically.
            if ($this->host && $this->port) {
                $this->open();
            }
        } else {
            Logger::get()->error(socket_strerror(socket_last_error()));
            throw new Exception(socket_strerror(socket_last_error()));
        }
    }

    /**
     * Open the connection to the socket.
     */
    public function open()
    {
        // Connect the socket.
        if (@!socket_connect($this->socket, $this->host, $this->port)) {
         Logger::get()->error(socket_strerror(socket_last_error()));
            throw new Exception('Socket connection error: ' . socket_strerror(socket_last_error()));
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    public function write($data)
    {
        if (socket_write($this->socket, $data) === false) {
         Logger::get()->error(socket_strerror(socket_last_error()));
            throw new Exception('Socket write error: ' . socket_strerror(socket_last_error()));
        } else {
         Logger::get()->debug("[To socket] {$data}");
        }
    }

    public function read()
    {
        $data = "";

        // Checks if there is something in the buffer, so a call
        // to read with an empty buffer does not block.
        $bufferLength = @socket_recvfrom($this->socket, $buffer, $this->bufferSize, 2, $this->host, $this->port);

        // If there is, retrieve it.
        if ($bufferLength > 0) {
            socket_recv($this->socket, $data, $bufferLength, 0);
            if ($data === false) {
             Logger::get()->error(socket_strerror(socket_last_error()));
                throw new Exception('Socket read error: ' . socket_strerror(socket_last_error()));
            }
        }

     Logger::get()->debug("[From socket] {$data}");

        return $data;
    }

    public function setProtocol($protocol)
    {
        $this->protocol = getprotobyname($protocol);

        if ($this->protocol == -1) {
            throw Exception("Socket: protocol '{$protocol}' not recognized", array($protocol));
        }
    }

}

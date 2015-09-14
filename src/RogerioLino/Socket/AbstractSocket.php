<?php

namespace RogerioLino\Socket;

/**
 * AbstractSocket.
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class AbstractSocket implements Socket
{
    protected $port = 3000;
    protected $address = '127.0.0.1';
    protected $blocking = true;
    protected $buffer = 2048;
    protected $backlog = 15;
    protected $type = SOCK_STREAM;
    protected $family = AF_INET;
    protected $protocol = SOL_TCP;
    protected $resource;

    /**
     * @param type $option
     *
     * @return type
     */
    public function getOption($option)
    {
        return socket_get_option($this->resource, SOL_SOCKET, $option);
    }

    /**
     * @param type $option
     * @param type $value
     *
     * @return type
     */
    public function setOption($option, $value)
    {
        return socket_set_option($this->resource, SOL_SOCKET, $option, $value);
    }

    /**
     * Returns the socket port.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Define the socket port.
     *
     * @param int $port
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param type $address
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Returns if the socket is blocking mode.
     *
     * @return bool
     */
    public function isBlocking()
    {
        return $this->blocking;
    }

    /**
     * Defines whether the socket is blocking or nonblocking mode.
     *
     * @param bool $block
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setBlocking($blocking)
    {
        $this->blocking = ($blocking == true);
        $this->updateBlockingMode();

        return $this;
    }

    /**
     * Returns the socket communication type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Define the socket communication type.
     *
     * @param int $type
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setType($type)
    {
        if ($type == SOCK_STREAM || $type == SOCK_DGRAM || $type == SOCK_SEQPACKET || $type == SOCK_RAW || $type == SOCK_RDM) {
            $this->type = $type;
        } else {
            throw new SocketException('Invalid socket communication type');
        }

        return $this;
    }

    /**
     * Returns the socket protocol family.
     *
     * @return int
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * Define the socket protocol family.
     *
     * @param int $family
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setFamily($family)
    {
        if ($family == AF_INET || $family == AF_INET6 || $family == AF_UNIX) {
            $this->family = $family;
        } else {
            throw new SocketException('Invalid socket protocol family');
        }

        return $this;
    }

    /**
     * Returns the socket protocol.
     *
     * @return int
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Define the socket protocol, must be compatible whit the protocol family.
     *
     * @param int $protocol
     *
     * @return \RogerioLino\Socket\AbstractSocket
     */
    public function setProtocol($protocol)
    {
        if ($protocol == SOL_TCP || $protocol == SOL_UDP || $protocol == SOL_SOCKET) {
            $this->protocol = $protocol;
        } else {
            throw new SocketException('Invalid socket protocol');
        }

        return $this;
    }

    /**
     * Binds to a socket.
     *
     * @throws SocketException
     */
    public function bind()
    {
        if (socket_bind($this->resource, $this->address, $this->port) === false) {
            throw new SocketException('Socket bind failed: '.$this->error());
        }
    }

    /**
     * Listens for a connection on a socket.
     *
     * @throws SocketException
     */
    public function listen()
    {
        if (socket_listen($this->resource, $this->backlog) === false) {
            throw new SocketException('Socket listen failed: '.$this->error());
        }
    }

    /**
     * Accepts a connection.
     *
     * @throws SocketException
     *
     * @return Socket
     */
    public function accept()
    {
        $sock = @socket_accept($this->resource);
        if ($sock === false) {
            throw new SocketException('Socket accept failed: '.$this->error());
        }

        return $sock;
    }

    /**
     * Reads data from the connected socket.
     *
     * @return string
     */
    public function read()
    {
        $data = '';
        while (($m = @socket_read($this->resource, $this->buffer)) != '') {
            $data .= $m;
        }

        return $data;
    }

    /**
     * Receives data from a connected socket.
     *
     * @return string
     */
    public function recv()
    {
        $len = @socket_recv($this->resource, $data, $this->buffer, 0);
        if ($len == 0) {
            return false;
        }

        return $data;
    }

    /**
     * Write the data to connected socket. Returns the number of bytes successfully written.
     *
     * @param string $message
     *
     * @return int
     */
    public function write($data)
    {
        return socket_write($this->resource, $data, strlen($data));
    }

    /**
     * 
     */
    public function send($data)
    {
        return socket_send($this->resource, $data, strlen($data), 0);
    }

    /**
     * Initiates a connection on a socket.
     *
     * @throws SocketException
     *
     * @return bool
     */
    public function connect()
    {
        try {
            return socket_connect($this->resource, $this->address, $this->port);
        } catch (SocketException $e) {
            return false;
        }
    }

    /**
     * Close the socket connection.
     */
    public function close()
    {
        socket_close($this->resource);
    }

    private function updateBlockingMode()
    {
        if ($this->blocking) {
            socket_set_block($this->resource);
        } else {
            socket_set_nonblock($this->resource);
        }
    }

    public function error()
    {
        return socket_strerror(socket_last_error($this->resource));
    }
}

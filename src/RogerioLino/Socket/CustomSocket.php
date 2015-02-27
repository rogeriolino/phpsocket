<?php

namespace RogerioLino\Socket;

/**
 * CustomSocket
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class CustomSocket extends AbstractSocket 
{

    /**
     * Socket constructor
     * @param mixed $prop
     */
    public function __construct($prop = null) {
        if (is_resource($prop)) {
            $this->resource = $prop;
        } else {
            if (is_array($prop)) {
                $this->port = (isset($prop['port'])) ? $prop['port'] : $this->port;
                $this->address = (isset($prop['address'])) ? $prop['address'] : $this->address;
                $this->family = (isset($prop['family'])) ? $prop['family'] : $this->family;
                $this->type = (isset($prop['type'])) ? $prop['type'] : $this->type;
                $this->protocol = (isset($prop['protocol'])) ? $prop['protocol'] : $this->protocol;
            }
            $this->resource = socket_create($this->family, $this->type, $this->protocol);
        }
    }
    
    /**
     * Accepts a connection. Wrapper to return a CustomSocket instance
     * @throws SocketException
     * @return Socket
     */
    public function accept() {
        return new CustomSocket(parent::accept());
    }

}


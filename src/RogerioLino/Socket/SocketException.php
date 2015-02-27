<?php

namespace RogerioLino\Socket;

/**
 * SocketException
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class SocketException extends \Exception 
{

    public function  __construct($message, $code = 0, $previous = null) {
        $this->message = $message;
        $this->code = $code;
        $this->message = $previous;
    }

}


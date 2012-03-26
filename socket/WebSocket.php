<?php
namespace socket;

use socket\Socket;
use socket\CustomSocket;

/**
 * WebSocket
 *
 * @author rogeriolino
 * @version 1.0.2
 */
class WebSocket extends CustomSocket {
    
    const HANDSHAKE_HYBI = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: {key}\r\n";
    const HANDSHAKE_HIXIE = "HTTP/1.1 101 Web Socket Protocol Handshake\r\nUpgrade: WebSocket\r\nConnection: Upgrade\r\nSec-WebSocket-Origin: {origin}\r\nSec-WebSocket-Location: ws://{url}\r\n";
    const GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
    
    public function headers($req) {
        $headers = array();
        $patterns = array(
            'resource' => "/GET (.*) HTTP/",
            'host' => "/Host: (.*)\r\n/",
            'origin' => "/Origin: (.*)\r\n/",
            'version' => "/Sec-WebSocket-Version: (.*)\r\n/",
            'sec-key' => "/Sec-WebSocket-Key: (.*)\r\n/",
            'sec-key1' => "/Sec-WebSocket-Key1: (.*)\r\n/",
            'sec-key2' => "/Sec-WebSocket-Key2: (.*)\r\n/",
            'sec-key3' => "/Sec-WebSocket-Key3: (.*)\r\n/",
            'cookie' => "/Cookie: (.*)\r\n/"
        );
        foreach ($patterns as $k => $v) {
            if (preg_match($v, $req, $match)) {
                $headers[$k] = $match[1];
            }
        }
        if (preg_match("/\r\n(.*?)\$/", $req, $match)) { 
            $headers['data'] = $match[1]; 
        }
        $headers['last8'] = substr($req, -8);
        return $headers;
    }
    
    public function handshake(AbstractSocket $socket) {
        $data = $socket->recv();
        $headers = $this->headers($data);
        $version = "";
        // check HyBi/IETF version
        if (isset($headers['version'])) {
            # HyBi-07 report version 7
            # HyBi-08 - HyBi-12 report version 8
            # HyBi-13 reports version 13
            if (in_array($headers['version'], array('7', '8', '13'))) {
                $version = "hybi-" . $headers['version'];
            } else {
                new SocketException("Unsupported protocol version: " . $headers['version']);
            }
            $key = $headers['sec-key'];

            # Generate the hash value for the accept header
            $accept = $key . self::GUID;
            $accept = sha1($accept);
            $accept = pack ('H*', $accept);
            $accept = base64_encode($accept);

            $response = str_replace("{key}", $accept, self::HANDSHAKE_HYBI);
            $socket->write($response . "\r\n");
            return true;
        }
        // Hixie version (75 or 76)
        else {
            $socket->write("Protocol not implemented");
            return false;
        }
    }
    
    public function decode($data) {
        $len = $masks = $msg = $decoded = null;
        $len = ord($data[1]) & 127;
        if ($len === 126) {
            $masks = substr($data, 4, 4);
            $msg = substr($data, 8);
        } else if ($len === 127) {
            $masks = substr($data, 10, 4);
            $msg = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $msg = substr($data, 6);
        }
        for ($index = 0; $index < strlen ($msg); $index++) {
            $decoded .= $msg[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }
    
    public function encode($data) {
        $frame = Array();
        $encoded = "";
        $frame[0] = 0x81;
        $data_length = strlen($data);
        if ($data_length <= 125) {
            $frame[1] = $data_length;    
        } else {
            $frame[1] = 126;  
            $frame[2] = $data_length >> 8;
            $frame[3] = $data_length & 0xFF; 
        }
        for ($i = 0; $i < sizeof($frame); $i++) {
            $encoded .= chr($frame[$i]);
        }
        $encoded .= $data;
        return $encoded;
    }

}

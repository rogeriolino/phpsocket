<?php
require_once('../bootstrap.php');

use socket\WebSocket;

set_time_limit(0);
ob_implicit_flush();

try {
    $options = array('address' => 'localhost', 'port' => 12345);
    $ws = new WebSocket($options);
    $ws->setOption(SO_REUSEADDR, true);
    $ws->setBlocking(false);
    $ws->bind();
    $ws->listen();

    $clients = array();
    
    _log("Connected");
    _log("Listen at {$ws->getAddress()}:{$ws->getPort()}");

    while (true) {
        try {
            $sock = $ws->accept();
        } catch (Exception $e) {
            $sock = null;
        }
        if ($sock) {
            if ($ws->handshake($sock)) {
                $client = client($sock);
                $clients[$client['id']] = $client;
                _log("Accepted new client: ". $client['id']);
            }
        }
        foreach ($clients as $id => $client) {
            $now = time();
            $data = $client['sock']->recv();
            if (!empty($data)) {
                $data = $ws->decode($data);
                _log("Received: " . $data);
                $clients[$id]['last'] = $now;
                $clients[$id]['ping'] = false;
            }
            // check inactivity (1 min)
            $elapsed = $now - $client['last'];
            if ($elapsed > 60) {
                // send ping message
                if ($elapsed < 80) {
                    if (!$client['ping']) {
                        _log("Sent ping to client: ". $client['id']);
                        $client['sock']->write($ws->encode('ping'));
                        $clients[$id]['ping'] = true;
                    }
                } else {
                    $client['sock']->write('timeout');
                    $client['sock']->write($ws->encode('disconnected by timeout'));
                    disconnect($id, "timeout");
                }
            }
        }

    }
} catch (Exception $e) {
    _log("Unable to connect: " . $e->getMessage());
}

function _log($msg) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] $msg\n";
}

function client($sock) {
    $sock->setBlocking(false);
    return array(
        'id' => time(),
        'sock' => $sock,
        'last' => time(),
        'ping' => false
    );
}

function disconnect($id, $msg = "") {
    global $clients;
    if (isset($clients[$id])) {
        $clients[$id]['sock']->close();
        unset($clients[$id]);
        $out = "Client disconected: $id";
        if (!empty($msg)) {
            $out .= " ($msg)";
        }
        _log("$out");
    } else {
        _log("trying disconect unknow client");
    }
}
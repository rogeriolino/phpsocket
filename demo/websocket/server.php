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
                // updating users list
                foreach ($clients as $_id => $_cli) {
                    if ($_id != $client['id']) {
                        send($_cli, message(users($client['id']), 'users'));
                    }
                }
            }
        }
        foreach ($clients as $id => $client) {
            $now = time();
            $message = recv($client);
            if ($message) {
                if (isset($message->data)) {
                    switch ($message->action) {
                    case 'message':
                        // message repassing
                        foreach ($clients as $_id => $_cli) {
                            if ($_id != $id) {
                                send($_cli, message($message->data, 'message', $_id));
                            }
                        }
                        break;
                    case 'users':
                        send($client, message(users($id), $message->action));
                        break;
                    }
                }
                $clients[$id]['last'] = $now;
                $clients[$id]['ping'] = false;
            }
            // check inactivity (1 min)
            $elapsed = $now - $client['last'];
            if ($elapsed > 60) {
                // send ping message
                if ($elapsed < 80) {
                    if (!$client['ping']) {
                        send($client, message('ping'));
                        $clients[$id]['ping'] = true;
                    }
                } else {
                    send($client, message('disconnected by timeout'));
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

function recv($client) {
    global $ws;
    $data = $client['sock']->recv();
    if (!empty($data)) {
        $data = $ws->decode($data);
        _log("Received: " . $data);
        return json_decode($data);
    }
    return null;
}

function users($skip) {
    global $clients;
    $users = array('you');
    foreach ($clients as $_id => $_cli) {
        if ($_id != $skip) {
            $users[] = $_id;
        }
    }
    return $users;
}

function message($data, $action = 'message', $user = null) {
    $message = array('action' => $action, 'data' => $data);
    // prevent overhead
    if ($user !== null) {
        $message['user'] = $user;
    }
    return $message;
}

function send($client, $message) {
    global $ws;
    $message = json_encode($message);
    @$client['sock']->write($ws->encode($message));
    _log("Sent: ". $message);
}

function client($sock) {
    global $clients;
    $ids = array_keys($clients);
    $sock->setBlocking(false);
    do {
        $id = "user-" . rand(0, 99999);
    } while (in_array($id, $ids));
    return array(
        'id' => $id,
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
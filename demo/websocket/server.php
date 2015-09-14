<?php

require_once dirname(dirname(__FILE__)).'/bootstrap.php';

use RogerioLino\Socket as Socket;

set_time_limit(0);
ob_implicit_flush();

class Client
{
    public $id;
    public $socket;
    public $last;
    public $pinging;

    public function __construct($id, $socket)
    {
        $this->id = $id;
        $this->socket = $socket;
        $this->last = time();
        $this->pinging = false;
    }
}

class Message
{
    public $action;
    public $data;
    public $user;

    public function __construct($action, $data = '', $user = '')
    {
        $this->action = $action;
        $this->data = $data;
        $this->user = $user;
    }

    public function toJson()
    {
        $message = ['action' => $this->action, 'data' => $this->data];
        // prevent overhead
        if (!empty($this->user)) {
            $message['user'] = $this->user;
        }

        return json_encode($message);
    }
}

class Server
{
    public $socket;
    public $clients = [];
    public $welcomeText = '<font style="white-space: pre;font-family:monospace, sans;font-weight:bold;">==========================================
              Welcome to
          WebSocket Chat Demo
       
 <a href="http://github.com/rogeriolino/phpsocket" target="_blank">http://github.com/rogeriolino/phpsocket</a>
==========================================</font>';

    public function __construct($options)
    {
        $this->socket = new Socket\WebSocket($options);
        $this->socket->setOption(SO_REUSEADDR, true);
        $this->socket->setBlocking(false);
    }

    public function run()
    {
        $this->socket->bind();
        $this->socket->listen();
        $this->log('Connected');
        $this->log("Listen at {$this->socket->getAddress()}:{$this->socket->getPort()}");

        while (true) {
            try {
                $sock = $this->socket->accept();
            } catch (Exception $e) {
                $sock = null;
            }
            if ($sock) {
                if ($this->socket->handshake($sock)) {
                    $client = $this->createClient($sock);
                    $this->appendClient($client);
                }
            }
            foreach ($this->clients as $client) {
                $now = time();
                $message = $this->receiveFrom($client);
                if ($message) {
                    if (isset($message->data)) {
                        switch ($message->action) {
                        case 'message':
                            // message repassing
                            $this->sendAll(new Message('message', $message->data, $client->id), $client);
                            break;
                        case 'users':
                            $this->sendTo($client, new Message($message->action, $this->getUsersList($client)));
                            break;
                        }
                    }
                    $client->last = $now;
                    $client->pinging = false;
                }
                // check inactivity (1 min)
                $elapsed = $now - $client->last;
                if ($elapsed > 60) {
                    // send ping message
                    if ($elapsed < 80) {
                        if (!$client->pinging) {
                            $this->sendTo($client, new Message('ping'));
                            $client->pinging = true;
                        }
                    } else {
                        $this->removeClient($client, 'disconnected by timeout');
                    }
                }
            }
        }
    }

    public function createClient($socket)
    {
        $ids = array_keys($this->clients);
        $socket->setBlocking(false);
        // dummy unique id generation
        do {
            $id = 'user-'.rand(0, 99999);
        } while (in_array($id, $ids));

        return new Client($id, $socket);
    }

    public function appendClient(Client $client)
    {
        $this->clients[$client->id] = $client;
        $this->log('Accepted new client: '.$client->id);
        // send welcome
        $this->sendTo($client, new Message('welcome', $this->welcomeText));
        // server message
        $this->sendAll(new Message('message', "user {$client->id} joined to chat", 'server'), $client);
        // updating users list
        $this->sendAll(function ($server, $cli) { return new Message('users', $server->getUsersList($cli)); });
    }

    public function sendTo(Client $client, Message $message)
    {
        $encoded = $message->toJson();
        $this->socket->writeTo($client->socket, $encoded);
        $this->log('Sent: '.$encoded);
    }

    public function sendAll($message, Client $skip = null)
    {
        foreach ($this->clients as $cli) {
            if ($skip == null || $cli->id != $skip->id) {
                if (is_callable($message)) {
                    $message = $message($this, $cli);
                }
                if ($message instanceof Message) {
                    $this->sendTo($cli, $message);
                }
            }
        }
    }

    public function receiveFrom(Client $client)
    {
        $data = $this->socket->recvFrom($client->socket);
        if (!empty($data)) {
            $this->log('Received: '.$data);

            return json_decode($data);
        }

        return;
    }

    public function removeClient(Client $client, $msg = '')
    {
        // server message
        $this->sendAll(new Message('message', "user {$client->id} disconnected", 'server'));
        // close connection
        $client->socket->close();
        unset($this->clients[$client->id]);
        $out = "Client disconected: {$client->id}";
        if (!empty($msg)) {
            $out .= " ($msg)";
        }
        $this->log("$out");
        // updating users list
        $this->sendAll(function ($server, $cli) { return new Message('users', $server->getUsersList($cli)); });
    }

    public function getUsersList(Client $skip = null)
    {
        $users = ['you'];
        foreach ($this->clients as $cli) {
            if ($skip == null || $cli->id != $skip->id) {
                $users[] = $cli->id;
            }
        }

        return $users;
    }

    public function log($msg)
    {
        $date = date('Y-m-d H:i:s');
        echo "[$date] $msg\n";
    }
}

$options = ['address' => '0.0.0.0', 'port' => 8000];
$server = new Server($options);
try {
    $server->run();
} catch (Exception $e) {
    $server->log('Unable to connect: '.$e->getMessage());
}

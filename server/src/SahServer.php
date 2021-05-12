<?php

namespace Fortress;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\Table;
use Swoole\WebSocket\Frame;

class SahServer
{
    private $server;
    private $logger;
    private $connections;

    public function __construct()
    {
        // create a log channel
        $this->logger = new Logger('SahServer');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $this->connections = new Table(1024);
        $this->connections->column('client', Table::TYPE_INT, 4);
        $this->connections->create();
        
        $server = new Server("0.0.0.0", 9502);
        $this->server = $server;

        $server->on("start", function (Server $server) {
            $this->logger->info("Swoole WebSocket Server is started at http://127.0.0.1:9502");
        });

        $server->on('open', function (Server $server, \Swoole\Http\Request $request) {
            $this->logger->info("connection open: {$request->fd}");
            // store the client on our memory table
            $this->connections->set($request->fd, ['client' => $request->fd]);
            $this->logger->info("We have " . $this->connections->count() . " connections active");
        });

        $server->on('message', function (Server $server, Frame $frame) {
            $this->logger->info("received message: {$frame->data}");
            $server->push($frame->fd, json_encode(["hello", time()]));
        });

        $server->on('close', function (Server $server, int $fd) {
            $this->logger->info("connection close: {$fd}");
            // remove the client from the memory table
            $this->connections->del($fd);
            $this->logger->info("We have " . $this->connections->count() . " connections active");
        });
    }

    public function start()
    {
        $this->server->start();
    }
}

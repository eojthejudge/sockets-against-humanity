<?php

namespace Fortress;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

class SahServer
{
    private $server;
    private $logger;

    public function __construct()
    {
        // create a log channel
        $this->logger = new Logger('SahServer');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        
        $server = new Server("0.0.0.0", 9502);
        $this->server = $server;

        $server->on("start", function (Server $server) {
            $this->logger->info("Swoole WebSocket Server is started at http://127.0.0.1:9502");
        });

        $server->on('open', function (Server $server, Swoole\Http\Request $request) {
            $this->logger->info("connection open: {$request->fd}");
            $server->tick(1000, function () use ($server, $request) {
                $server->push($request->fd, json_encode(["hello", time()]));
            });
        });

        $server->on('message', function (Server $server, Frame $frame) {
            $this->logger->info("received message: {$frame->data}");
            $server->push($frame->fd, json_encode(["hello", time()]));
        });

        $server->on('close', function (Server $server, int $fd) {
            $this->logger->info("connection close: {$fd}");
        });
    }

    public function start()
    {
        $this->server->start();
    }
}

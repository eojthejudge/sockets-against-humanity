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
    private $whiteCards;

    public function __construct()
    {
        // create a log channel
        $this->logger = new Logger('SahServer');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $this->connections = new Table(1024);
        $this->connections->column('client', Table::TYPE_INT, 4);
        $this->connections->column('username', Table::TYPE_STRING, 64);
        $this->connections->create();
        $this->whiteCards = new Table(2048);
        $this->whiteCards->column('id', Table::TYPE_INT, 8);
        $this->whiteCards->column('text', Table::TYPE_STRING, 128);
        $this->whiteCards->create();

        $this->initData();
        
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
            $dataArray = json_decode($frame->data);
            $this->logger->debug('Received: ', array($dataArray));
            if ($dataArray && !empty($dataArray->user)) {
                $this->connections->set($frame->fd, ['username' => $dataArray->user]);
            }
            $server->push($frame->fd, json_encode(["hello", time()]));
        });

        $server->on('close', function (Server $server, int $fd) {
            $this->logger->info("connection close: {$fd}");
            $userName = $this->connections->get($fd, 'username');
            if (!empty($userName)) {
                $this->logger->info($userName . ' has left the game');
            }
            // remove the client from the memory table
            $this->connections->del($fd);
            $this->logger->info("We have " . $this->connections->count() . " connections active");
        });
    }

    public function start()
    {
        $this->server->start();
    }

    public function initData() : void
    {
        $dataString = file_get_contents(__DIR__ . "/../data/cah-cards.json");
        $dataJson = json_decode($dataString);

        foreach($dataJson->white as $key => $whiteCard) {
            $this->whiteCards->set($key, ['id' => $key, 'text' => $whiteCard]);
        }
        $this->logger->info("Imported " . $this->whiteCards->count() . " from data/cah-cards.json");
    }
}

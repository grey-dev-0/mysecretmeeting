<?php

namespace App\Console\Commands;

use App\Http\Controllers\WebsocketController;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class WebsocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes signaling server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(){
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new WebsocketController()
                )
            ),
            env('QUEUESOCKET_PORT', 8090)
        );
        $server->run();
    }
}

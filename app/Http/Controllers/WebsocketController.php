<?php namespace App\Http\Controllers;

use App\Services\RoomHandler;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebsocketController extends Controller implements MessageComponentInterface{
    /**
     * @var RoomHandler $roomHandler Handles created rooms and participating peers.
     */
    private $roomHandler;

    public function __construct(){
        $this->roomHandler = app(RoomHandler::class);
    }

    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $connection){
        $this->roomHandler->addConnection($connection);
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $connection){
        $this->roomHandler->peerDisconnected($connection->resourceId);
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $connection, \Exception $e){
        \Log::error("{$e->getMessage()}\n{$e->getTraceAsString()}");
        $connection->close();
    }

    /**
     * @inheritDoc
     */
    function onMessage(ConnectionInterface $connection, $message){
        $message = json_decode($message, true);
        if(empty($message['action']))
            return;
        switch($message['action']){
            case 'init': $this->roomHandler->initializePeers($connection, $message); break;
            case 'query': $this->roomHandler->roomExists($connection, $message['code']); break;
            case 'record': $this->roomHandler->initRecorder($connection, $message); break;
            case 'offer':
            case 'answer':
            case 'candidate':
                $this->roomHandler->sendSdpOrIce($message, $connection->resourceId);

        }
        fwrite(STDOUT, json_encode($message)."\n");
    }
}

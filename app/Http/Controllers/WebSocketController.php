<?php namespace App\Http\Controllers;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebSocketController extends Controller implements MessageComponentInterface{
    private $connections;

    public function __construct(){
        $this->connections = collect();
    }
    
    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $connection The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $connection){
        $this->connections->put($connection->resourceId, ['qr_code' => sha1("{$connection->resourceId}_"
                .date('Y.m.d_H:i:s'))] + compact('connection'));
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $connection The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $connection){
        $this->publishData($connection, ['action' => 'close']);
        $message = "Connection #{$connection->resourceId}";
        if(!is_null($this->connections->get($connection->resourceId)['qr_code']))
            $message .= " of QR code {$this->connections[$connection->resourceId]['qr_code']}";
        $this->connections->forget($connection->resourceId);
        fwrite(STDOUT, '['.date('Y-m-d h:i:s A')."] $message disconnected.\n");
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $connection
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $connection, \Exception $e){
        fwrite(STDOUT, '['.date('Y-m-d h:i:s A')."] Error on socket #{$connection->resourceId}.\n".$e->getMessage()
            ."\n".$e->getTraceAsString()."\n");
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $connection The socket/connection that sent the message to your application
     * @param  string $message The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $connection, $message){
        $message = json_decode($message, true);
        switch($message['action']){
            case 'init':
                if(!empty($message['qr_code'])){
                    $peer = $this->connections->get($connection->resourceId);
                    $peer['qr_code'] = $message['qr_code'];
                    $this->connections->put($connection->resourceId, $peer);
                    $peers = $this->getRelatedPeers($connection->resourceId);
                } else{
                    $message['qr_code'] = $this->connections->get($connection->resourceId)['qr_code'];
                    $peers = [];
                }
                $this->sendMessage($connection, $message + ['id' => $connection->resourceId] + compact('peers')); break;
            case 'query': $this->sendMessage($connection,
                ['success' => $this->connections->where('qr_code', $message['qr_code'])->count() > 0]); break;
            case 'sdp': $this->publishSdp($connection, $message); break;
            case 'candidate': $this->publishIce($connection, $message); break;
        }
    }

    /**
     * Sends a message to a connection.
     *
     * @param $connection ConnectionInterface
     * @param $message array | string
     */
    private function sendMessage(&$connection, $message){
        if(is_array($message))
            $message = json_encode($message);
        $connection->send($message);
    }

    /**
     * Getting connection IDs of the peers that share the same QR code of the given connection ID.
     *
     * @param $resourceId int Connection ID.
     * @return int[]
     */
    private function getRelatedPeers($resourceId){
        $peers = $this->connections->where('qr_code', $this->connections[$resourceId]['qr_code']);
        $ids = [];
        $peers->each(function($peer, $id) use(&$ids, $resourceId){
            if($resourceId != $id)
                $ids[] = $id;
        });
        return $ids;
    }

    /**
     * Publishing Web RTC SDP to related peers.
     *
     * @param $connection
     * @param $message
     */
    private function publishSdp(&$connection, $message){
        $this->publishData($connection, $message);
    }

    /**
     * Publishing Web RTC ICE candidates to related peers.
     *
     * @param $connection
     * @param $message
     */
    private function publishIce(&$connection, $message){
        $this->publishData($connection, $message);
    }

    /**
     * Publishing Web RTC network and / or media data to related peers.
     *
     * @param $connection
     * @param $message
     */
    private function publishData(&$connection, $message){
        $connections = $this->connections->where('qr_code', $this->connections->get($connection->resourceId)['qr_code']);
        $message['id'] = $connection->resourceId;
        $message = json_encode($message);
        foreach($connections as $resourceId => &$peer)
            if($connection->resourceId != $resourceId)
                $peer['connection']->send($message);
    }
}

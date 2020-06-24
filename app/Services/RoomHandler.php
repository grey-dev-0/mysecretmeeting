<?php namespace App\Services;

use App\Peer;
use App\Room;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;
use function Sodium\compare;

class RoomHandler{
    /**
     * @var ConnectionInterface[] $connections Current connected peers.
     */
    private $connections = [];

    /**
     * Adds new websocket connection to server memory for later contact.
     *
     * @param $connection ConnectionInterface
     */
    public function addConnection($connection){
        $this->connections[$connection->resourceId] = $connection;
    }

    /**
     * Gets the websocket connection by its given ID.
     *
     * @param $connectionId int
     * @return ConnectionInterface
     */
    public function getConnection($connectionId){
        return $this->connections[$connectionId];
    }

    /**
     * Gets websocket connections that match the given IDs.
     *
     * @param $connectionIds
     * @return ConnectionInterface[]
     */
    public function getConnections($connectionIds){
        $connections = [];
        foreach($connectionIds as $id)
            $connections[] = $this->connections[$id];
        return $connections;
    }

    public function peerDisconnected($peerId){
        if(is_null($peer = Peer::find($peerId)))
            return;
        $roomId = $peer->room_id;
        $peer->delete();
        if(Peer::whereRoomId($roomId)->count() == 0)
            Room::whereId($roomId)->delete();
        else
            $this->sendMessage($this->getConnections(Peer::whereRoomId($roomId)->pluck('id')),
                ['action' => 'close', 'id' => $peerId]);
        unset($this->connections[$peerId]);
    }

    public function peerInitialized($roomId, $peerId){
        if(empty($roomId) || is_null($room = Room::find($roomId)))
            $room = Room::create(['id' => sha1($peerId.'_'.date('Y-m-d.H_i_s'))]);
        $room->peers()->create(['id' => $peerId]);
        return $room->id;
    }

    public function roomExists($client, $roomId){
        $this->sendMessage($client, ['action' => 'query', 'success' => Room::whereId($roomId)->count() > 0]);
    }

    /**
     * Initializes all peers in a room including the calling one.
     *
     * @param $caller ConnectionInterface The calling peer's connection
     * @param $message array The message that the caller has sent.
     */
    public function initializePeers($caller, &$message){
        $roomId = $this->peerInitialized($message['code'], $caller->resourceId);
        $this->sendMessage($caller, ['action' => 'init', 'id' => $caller->resourceId, 'local' => true, 'code' => $roomId]);
        $callees = $this->getConnections(Peer::whereRoomId($roomId)->where('id', '!=', $caller->resourceId)->pluck('id'));
        foreach($callees as $callee){
            $this->sendMessage($callee, ['action' => 'init', 'id' => $caller->resourceId, 'local' => false, 'code' => $roomId]);
            $this->sendMessage($caller, ['action' => 'init', 'id' => $callee->resourceId, 'local' => false, 'code' => $roomId]);
        }
    }

    public function sendSdpOrIce($message, $senderId){
        $message += compact('senderId');
        $this->sendMessage($this->connections[$message['id']], $message);
    }

    /**
     * Sends a message to a connection(s).
     *
     * @param $connections ConnectionInterface|ConnectionInterface[]
     * @param $message array
     */
    private function sendMessage($connections, $message){
        $message = json_encode($message);
        $connections = Arr::wrap($connections);
        foreach($connections as &$connection)
            $connection->send($message);
    }
}

<?php namespace App\Services;

use App\Models\Peer;
use App\Models\Room;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;

class RoomHandler{
    /**
     * @var ConnectionInterface[] $connections Current connected peers.
     */
    private $connections = [];

    /**
     * @var array $recorders The initialized room recorders.
     */
    private $recorders = [];

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
     * @param $connectionId ?int
     * @return ConnectionInterface
     */
    public function getConnection($connectionId){
        return $this->connections[$connectionId]?? null;
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
        if(is_null($peer = Peer::find($peerId))){
            if(isset($this->recorders[$peerId]))
                unset($this->recorders[$peerId]);
            return;
        }
        $roomId = $peer->room_id;
        $peer->delete();
        if(Peer::whereRoomId($roomId)->count() == 0)
            Room::whereId($roomId)->delete();
        else
            $this->sendMessage($this->getConnections(Peer::whereRoomId($roomId)->pluck('id')),
                ['action' => 'close', 'id' => $peerId]);
        unset($this->connections[$peerId]);
        $this->terminateRecording($roomId, $peerId);
    }

    public function peerInitialized($roomId, $peerId, $audio_only){
        if(empty($roomId) || is_null($room = Room::find($roomId))){
            $room = Room::create(['id' => sha1($peerId . '_' . date('Y-m-d.H_i_s'))]);
            $this->setupRecorder($room->id);
        }
        $room->peers()->create(['id' => $peerId] + compact('audio_only'));
        if(($recorderId = array_search($room->id, $this->recorders)) !== false)
            $this->sendMessage($this->connections[$recorderId], [
                'action' => 'peer',
                'id' => $peerId
            ] + compact('audio_only'));
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
        $roomId = $this->peerInitialized($message['code'], $caller->resourceId, $message['audio_only']);
        $time = time();
        $audio_only = $message['audio_only'];
        $this->sendMessage($caller, ['action' => 'init', 'id' => $caller->resourceId, 'local' => true, 'code' => $roomId]
            + compact('time', 'audio_only'));
        $callees = Peer::whereRoomId($roomId)->where('id', '!=', $caller->resourceId)->get(['id', 'audio_only']);
        foreach($callees as $callee){
            if(empty($connection = $this->getConnection($callee->id)))
                continue;
            $this->sendMessage($connection, ['action' => 'init', 'id' => $caller->resourceId, 'local' => false, 'code' => $roomId]
                + compact('time', 'audio_only'));
            $this->sendMessage($caller, ['action' => 'init', 'id' => $connection->resourceId, 'local' => false, 'code' => $roomId]
                + compact('time') + $callee->only(['audio_only']));
        }
    }

    /**
     * Invokes the recorder server for the provided room, if recording is enabled.
     *
     * @param string $roomId The ID of the room to be recorded.
     * @return void
     */
    private function setupRecorder($roomId){
        if(!env('RECORD_CALLS', false))
            return;
        $command = env('RECORD_COMMAND', './record')." --room=$roomId";
        if($size = env('RECORD_SIZE'))
            $command .= " --size=$size";
        if(env('AUDIO_ONLY', false))
            $command .= ' --audioOnly';
        `nohup $command > storage/logs/recording/$roomId.log 2>&1 &`;
    }

    /**
     * Initializes the recording process of a given room or peer.
     *
     * @param $connection ConnectionInterface The connection of the recording server.
     * @param $message array The initialization message sent by the recording server.
     * @return void
     */
    public function initRecorder($connection, $message){
        if(isset($message['room'])){
            $this->recorders[$connection->resourceId] = $message['room'];
            Peer::whereRoomId($message['room'])->get(['id', 'audio_only'])
                ->each(fn($peer) => $this->sendMessage($connection, ['action' => 'peer', 'id' => $peer->id]
                    + $peer->only(['audio_only'])));
        } elseif($peer = Peer::whereRoomId($this->recorders[$connection->resourceId])->whereId($message['peer'])->first(['id']))
            if($peerConnection = $this->getConnection($peer->id))
                $this->sendMessage($peerConnection, [
                    'action' => 'record',
                    'senderId' => $connection->resourceId,
                    'offer' => $message['offer']
                ]);
    }

    /**
     * Stops the recording process for the given peer who's disconnected from the provided room.
     *
     * @param int $roomId The ID of the room that the peer has exited from.
     * @param int $peerId The ID of the peer who exited.
     * @return void
     */
    public function terminateRecording($roomId, $peerId){
        $recorderId = array_search($roomId, $this->recorders);
        if($recorderId === false)
            return;
        $this->sendMessage($this->connections[$recorderId], [
            'action' => 'disconnect',
            'peer' => $peerId
        ]);
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

<?php namespace App\Http\Controllers;

use App\IceServer;

class MainController extends Controller{
    public function getIndex(){
        if(empty($qrCode = request('c', '')) || $this->sendToWebsocket(['action' => 'query', 'code' => request('c')]))
            return view('app', compact('qrCode') + ['iceServers' => IceServer::select(['url', 'type'])->whereActive(true)->get()]);
        return abort(404, 'Room Not Found!');
    }

    public function getRoomQr(){
        if($this->sendToWebsocket(['action' => 'query', 'code' => request('c')]))
            return response()->streamDownload(function(){
                echo \QrGenerator::writeString(url('/').'?c='.request('c'));
            }, 'qr.png');
        return abort(404, 'Room Not Found!');
    }

    private function sendToWebsocket($data){
        $loop = \React\EventLoop\Factory::create();
        $connector = new \React\Socket\Connector($loop);
        $connector = new \Ratchet\Client\Connector($loop, $connector);

        $connector('ws://127.0.0.1:'.env('WEBSOCKET_PORT', 8090))->then(function($connection) use(&$success, &$loop, &$data){
            $connection->on('message', function($message) use(&$connection, &$loop, &$success){
                $message = json_decode("$message", true);
                $success = $message['success'];
                $connection->close();
                $loop->stop();
            });
            $connection->send(json_encode($data));
        }, function() use (&$loop, &$success){
            $success = false;
            $loop->stop();
        });

        $loop->run();
        return $success;
    }
}

<?php namespace App\Http\Controllers;

class QrController extends Controller{
    public function getIndex(){
        if($this->sendToWebsocket(['action' => 'query', 'qr_code' => request('c')]))
            return response()->streamDownload(function(){
                echo \QrGenerator::writeString(url('/').'?c='.request('c'));
            }, 'qr.png');
        else
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

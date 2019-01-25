(function(Vue, $, ClipboardJS){
    $(document).ready(function(){
        window.rtc = new Vue({
            el: '#app',
            data: {
                config: {
                    iceServers: [
                        {url:'stun:stun.l.google.com:19302'},
                        {url:'stun:stun1.l.google.com:19302'},
                        {url:'stun:stun2.l.google.com:19302'},
                        {url:'stun:stun3.l.google.com:19302'}/*,
                        {url:'stun:stun4.l.google.com:19302'}*/
                    ]
                },
                connections: {
                    qr_code: null,
                    my_connection: {
                        websocket_id: null,
                        connection: null,
                        stream: null,
                        error: null
                    }
                },
                channels: {},
                websocket: null
            },
            methods: {
                init: function(domain){
                    this.websocket = new WebSocket('wss://' + domain + '/websocket');
                    this.websocket.onopen = function(){
                        rtc.websocket.send(JSON.stringify({
                            action: 'init',
                            qr_code: initCode
                        }));
                    };
                    this.websocket.onmessage = function(e){
                        var message = JSON.parse(e.data);
                        switch(message.action){
                            case 'init':
                                rtc.connections.qr_code = message.qr_code;
                                rtc.connections.my_connection.websocket_id = message.id;
                                rtc.connect(message.id);
                                rtc.call(message.peers);
                                new ClipboardJS('#copy-url');
                                break;
                            case 'sdp':
                                rtc.connect(message.id);
                                rtc.exchangeSdps(message.id, message.sdp);
                                break;
                        }
                    }
                },
                connect: function(websocketId){
                    var connection;
                    if(this.connections.my_connection.websocket_id == websocketId){
                        connection = this.connections.my_connection.connection = new RTCPeerConnection(this.config);
                        this.emitStream(connection);
                    } else{
                        connection = new RTCPeerConnection(this.config);
                        this.connections[websocketId] = {connection: connection, stream: null, error: null};
                        connection.onaddstream = function(e){
                            rtc.connections[websocketId].stream = e.stream;
                        };
                    }
                    connection.onicecandidate = function(e){
                        rtc.websocket.send(JSON.stringify({
                            action: 'candidate',
                            candidate: e.candidate
                        }));
                    };
                },
                emitStream: function(connection){
                    var setConnectionStream = function(stream){
                        rtc.connections.my_connection.stream = stream;
                        setTimeout(function(){
                            rtc.$refs.my_video[0].srcObject = stream;
                        });
                        connection.addStream(stream);
                    };
                    var streamError = function(error){
                        rtc.connections.my_connection.error = error.message;
                    };
                    var streamConfig = {audio: true, video: true};
                    switch(true){
                        case (navigator.getUserMedia !== undefined):
                            navigator.getUserMedia(streamConfig, setConnectionStream, streamError); break;
                        case (navigator.webkitGetUserMedia !== undefined):
                            navigator.webkitGetUserMedia(streamConfig, setConnectionStream, streamError); break;
                        case (navigator.mediaDevices !== undefined && navigator.mediaDevices.getUserMedia !== undefined):
                            navigator.mediaDevices.getUserMedia(streamConfig).then(setConnectionStream)
                                .catch(streamError); break;
                        case (navigator.mozGetUserMedia !== undefined):
                            navigator.mozGetUserMedia(streamConfig, setConnectionStream, function(){}); break;
                        default: alert('Could NOT access Audio / Video Input!');
                    }
                },
                call: function(peerIds){
                    for(var i in peerIds){
                        if(rtc.connections[peerIds[i]] === undefined)
                            this.connect(peerIds[i]);
                        setTimeout(function(){
                            rtc.connections[peerIds[i]].connection.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true }).then(function(offer){
                                rtc.connections[peerIds[i]].connection.setLocalDescription(offer);
                                rtc.websocket.send(JSON.stringify({
                                    action: 'sdp',
                                    sdp: offer
                                }));
                            });
                        }, 1000);
                    }
                },
                exchangeSdps: function(websocketId, sdp){
                    var connection = (this.connections.my_connection.websocket_id == websocketId)?
                        this.connections.my_connection.connection : rtc.connections[websocketId].connection;
                    if(sdp.type == 'offer'){
                        setTimeout(function(){
                            connection.setRemoteDescription(new RTCSessionDescription(sdp));
                            connection.createAnswer().then(function(answer){
                                connection.setLocalDescription(answer);
                                rtc.websocket.send(JSON.stringify({
                                    action: 'sdp',
                                    sdp: answer
                                }));
                            });
                        }, 1000);
                    } else
                        this.connections[websocketId].connection.setRemoteDescription(new RTCSessionDescription(sdp));
                }
            }
        });

        $('body').on('click', '#upload-qr, #capture-qr', function(){
            $(this).next('input').trigger('click');
        });
    });
})(Vue, jQuery, ClipboardJS);
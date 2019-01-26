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
                        error: null,
                        candidates: []
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
                                if(rtc.connections[message.id] === undefined)
                                    rtc.connect(message.id);
                                rtc.exchangeSdps(message.id, message.sdp);
                                break;
                            case 'candidate':
                                if(message.candidate != '' && message.candidate != null){
                                    if(!rtc.connections[message.id].connection.remoteDescription)
                                        rtc.connections[message.id].candidates.push(message.candidate);
                                    else
                                        rtc.connections[message.id].connection.addIceCandidate(new RTCIceCandidate(message.candidate));}
                                break;
                        }
                    }
                },
                connect: function(websocketId){
                    if(this.connections.my_connection.websocket_id == websocketId)
                        this.initLocalStream();
                    else{
                        var connection = new RTCPeerConnection(this.config);
                        this.$set(this.connections, websocketId, {connection: connection, stream: null, error: null, candidates: []});
                        connection.ontrack = function(e){
                            console.log('Received stream', e.streams, 'from connection', websocketId);
                            rtc.connections[websocketId].stream = e.streams[0];
                            rtc.$refs['video'+websocketId][0].srcObject = e.streams[0];
                        };
                        connection.onicecandidate = function(e){
                            rtc.websocket.send(JSON.stringify({
                                action: 'candidate',
                                candidate: e.candidate
                            }));
                        };
                    }
                },
                initLocalStream: function(){
                    var setConnectionStream = function(stream){
                        rtc.connections.my_connection.stream = stream;
                        setTimeout(function(){
                            rtc.$refs.my_video[0].srcObject = stream;
                        });
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
                        if(this.connections[peerIds[i]] === undefined)
                            this.connect(peerIds[i]);
                        this.connections[peerIds[i]].connection.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: true }).then(function(offer){
                            rtc.connections[peerIds[i]].connection.setLocalDescription(offer);
                            rtc.websocket.send(JSON.stringify({
                                action: 'sdp',
                                sdp: offer
                            }));
                        });
                    }
                },
                exchangeSdps: function(websocketId, sdp){
                    var connection = this.connections[websocketId].connection;
                    setTimeout(function(){
                        rtc.connections.my_connection.stream.getTracks().forEach(function(track){
                            console.log('Adding stream track', track, 'to connection', connection);
                            connection.addTrack(track, rtc.connections.my_connection.stream);
                        });
                        if(sdp.type == 'offer'){
                            connection.setRemoteDescription(new RTCSessionDescription(sdp));
                            connection.createAnswer().then(function (answer) {
                                connection.setLocalDescription(answer);
                                rtc.websocket.send(JSON.stringify({
                                    action: 'sdp',
                                    sdp: answer
                                }));
                                rtc.addCandidates(websocketId);
                            });
                        } else
                            connection.setRemoteDescription(new RTCSessionDescription(sdp)).then(function(){
                                rtc.addCandidates(websocketId);
                            });
                    }, 1000);
                },
                addCandidates: function(websocketId){
                    var candidates = this.connections[websocketId].candidates;
                    for(var i in candidates)
                        this.connections[websocketId].connection.addIceCandidate(candidates[i]);
                    this.connections[websocketId].candidates = [];
                }
            }
        });

        $('body').on('click', '#upload-qr, #capture-qr', function(){
            $(this).next('input').trigger('click');
        });
    });
})(Vue, jQuery, ClipboardJS);
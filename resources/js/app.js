(function($, ClipboardJS){
    $(document).ready(function(){
        window.rtc = {
            // Data
            config: {
                iceServers: [
                    {url:'stun:stun.12connect.com:3478'},
                    {url:'stun:stun.12voip.com:3478'},
                    {url:'stun:stun.1und1.de:3478'},
                    {url:'stun:stun3.l.google.com:19302'},
                    {url:'stun:stun4.l.google.com:19302'}
                ]
            },
            peerCell: '<div class="col"><div class="card"><div class="card-body row"><video class="col d-none" autoplay></video><h3 class="d-none text-center w-100"></h3></div></div></div>',
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
            websocket: null,

            // Methods
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
                            rtc.initLocalStream();
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
                if(this.connections.my_connection.websocket_id != websocketId){
                    var connection = new RTCPeerConnection(this.config);
                    this.connections[websocketId] = {connection: connection, stream: null, error: null, candidates: []};
                    $('#app').append($(this.peerCell).attr('id', websocketId));
                    connection.ontrack = function(e){
                        console.log('Received stream tracks', e.streams[0].getTracks(), 'from connection', websocketId);
                        rtc.connections[websocketId].stream = e.streams[0];
                        var video = $('#'+websocketId).find('video').removeClass('d-none')[0];
                        if(video.srcObject == null){
                            console.log('setting received stream.');
                            video.srcObject = e.streams[0];
                        } else{
                            console.log('adding track to current stream');
                            e.streams[0].getTracks().forEach(function(track){
                                video.srcObject.addTrack(track);
                            });
                        }
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
                    setTimeout(function(){
                        rtc.connections.my_connection.stream = stream;
                        $('#my-connection').find('video').removeClass('d-none')[0].srcObject = stream;
                    });
                };
                var streamError = function(error){
                    rtc.connections.my_connection.error = error.message;
                    $('#my-connection').find('h3').removeClass('d-none').text(error.message);
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
                    this.connections[peerIds[i]].connection.createOffer({offerToReceiveAudio: true, offerToReceiveVideo: true}).then(function(offer){
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
                    if(sdp.type == 'offer'){
                        connection.setRemoteDescription(new RTCSessionDescription(sdp));
                        connection.createAnswer({offerToReceiveAudio: true, offerToReceiveVideo: true}).then(function (answer) {
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
                    connection.onnegotiationneeded = function(){
                        rtc.call([websocketId]);
                    };
                    rtc.connections.my_connection.stream.getTracks().forEach(function(track){
                        console.log('Adding stream track', track, 'to connection ', connection);
                        connection.addTrack(track, rtc.connections.my_connection.stream);
                    });
                }, 1000);
            },
            addCandidates: function(websocketId){
                var candidates = this.connections[websocketId].candidates;
                for(var i in candidates)
                    this.connections[websocketId].connection.addIceCandidate(candidates[i]);
                this.connections[websocketId].candidates = [];
            }
        };

        $('body').on('click', '#capture-qr', function(){
            $(this).next('input').trigger('click');
        });
    });
})(jQuery, ClipboardJS);
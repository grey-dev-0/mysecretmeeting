(function(){
    var app = new Vue({
        el: '#app',
        name: 'MySecretMeeting',
        data: {
            roomId: qrCode,
            iceServers: iceServers,
            peers: [],
            signalingChannel: null,
            localStream: null
        },
        computed: {
            cells: function(){
                return this.peers.length + 1;
            }
        },
        methods: {
            initSignalingChannel: function(qrCode){
                this.signalingChannel = new WebSocket(baseUrl.replace(/^https?/, 'ws') + '/websocket');
                this.signalingChannel.onopen = function(){
                    app.signalingChannel.send(JSON.stringify({
                        action: 'init',
                        code: qrCode
                    }));
                };
                this.addSignalingListeners();
            },
            addSignalingListeners: function(){
                this.signalingChannel.onmessage = function(e){
                    var message = JSON.parse(e.data);
                    switch(message.action){
                        case 'init':
                            app.roomId = message.code;
                            app.$nextTick(function(){
                                if(message.local)
                                    app.$refs.qr.initQrButtons();
                                app.initPeer(message.id, message.local);
                            });
                            break;
                        case 'offer':
                            app.$refs['p-' + message.senderId][0].handleOffer(message.offer, message.senderId);
                            break;
                        case 'answer':
                            app.$refs['p-' + message.senderId][0].handleAnswer(message.answer);
                            break;
                        case 'candidate':
                            app.$refs['p-' + message.senderId][0].handleCandidate(message.candidate);
                            break;
                    }
                }
            },
            initPeer: function(peerId, local){
                this.peers.push({
                    id: peerId,
                    local: local
                });
            }
        }
    });
})();

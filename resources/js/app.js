(function(){
    var app = new Vue({
        el: '#app',
        name: 'MySecretMeeting',
        data: {
            roomId: qrCode,
            iceServers: iceServers,
            peers: [],
            signalingChannel: null,
            localStream: null,
            ready: false,
            pendingPeers: [],
            pendingMessages: []
        },
        computed: {
            cells: function(){
                return this.peers.length + 1;
            }
        },
        methods: {
            setLocalPeerReady: function(){
                this.ready = true;
                for(var i in this.pendingPeers)
                    this.peers.push(this.pendingPeers[i]);
                this.pendingPeers = [];
                setTimeout(function(){
                    for(var i in app.pendingMessages)
                        app.handleSignalingMessage(app.pendingMessages[i]);
                    app.pendingMessages = [];
                }, 1000);
            },
            initSignalingChannel: function(qrCode){
                this.signalingChannel = new WebSocket(baseUrl.replace(/^https?/, 'wss') + '/websocket');
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
                    if(message.action == 'init'){
                        app.roomId = message.code;
                        app.$nextTick(function(){
                            if(message.local)
                                app.$refs.qr.initQrButtons();
                            app.initPeer(message.id, message.local);
                        });
                    } else if(app.ready)
                        app.handleSignalingMessage(message);
                    else
                        app.pendingMessages.push(message);
                }
            },
            initPeer: function(peerId, local){
                var peer = {
                    id: peerId,
                    local: local
                };
                if(local || this.ready)
                    this.peers.push(peer);
                else
                    this.pendingPeers.push(peer)
            },
            handleSignalingMessage: function(message){
                switch(message.action){
                    case 'offer':
                        this.$refs['p-' + message.senderId][0].handleOffer(message.offer, message.senderId);
                        break;
                    case 'answer':
                        this.$refs['p-' + message.senderId][0].handleAnswer(message.answer);
                        break;
                    case 'candidate':
                        this.$refs['p-' + message.senderId][0].handleCandidate(message.candidate);
                        break;
                    case 'close':
                        var peerIndex = _.findIndex(this.peers, function(peer){
                            return peer.id == message.id;
                        });
                        if(peerIndex != -1)
                            this.peers.splice(peerIndex, 1);
                }
            }
        }
    });
})();

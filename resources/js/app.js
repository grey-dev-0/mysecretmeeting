import {createApp} from 'vue';
import Peer from "../components/peer";
import Qr from "../components/qr";
let libraries = {Peer, Qr};

let app = createApp({
    name: 'MySecretMeeting',
    data: {
        roomId: qrCode,
        iceServers: iceServers,
        peers: [],
        signalingChannel: null,
        localStream: null,
        ready: false,
        pendingPeers: [],
        pendingMessages: [],
        createdAt: 0
    },
    computed: {
        cells(){
            return this.peers.length + 1;
        }
    },
    methods: {
        setLocalPeerReady(){
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
        initSignalingChannel(qrCode){
            this.signalingChannel = new WebSocket(baseUrl.replace(/^https?/, 'wss') + '/websocket');
            this.signalingChannel.onopen = function(){
                app.signalingChannel.send(JSON.stringify({
                    action: 'init',
                    code: qrCode
                }));
            };
            this.addSignalingListeners();
        },
        addSignalingListeners(){
            this.signalingChannel.onmessage = function(e){
                var message = JSON.parse(e.data);
                if(message.action == 'init'){
                    app.roomId = message.code;
                    app.$nextTick(function(){
                        if(message.local){
                            app.createdAt = message.time;
                            app.$refs.qr.initQrButtons();
                        }
                        app.initPeer(message.id, message.local, message.time);
                    });
                } else if(app.ready)
                    app.handleSignalingMessage(message);
                else
                    app.pendingMessages.push(message);
            }
        },
        initPeer(peerId, local, time){
            var peer = {
                id: peerId,
                local: local,
                time: time
            };
            if(local || this.ready)
                this.peers.push(peer);
            else
                this.pendingPeers.push(peer)
        },
        handleSignalingMessage(message){
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

for(var component in libraries)
    app.component(component, libraries[component]);
app.mount('#app');
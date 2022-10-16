import {createApp} from 'vue';
import Modal from "../components/modal";
import Peer from "../components/peer";
import Qr from "../components/qr";
import {findIndex as _findIndex} from 'lodash';
let libraries = {Modal, Peer, Qr};

let app = createApp({
    name: 'MySecretMeeting',
    data: () => ({
        roomId: qrCode,
        iceServers: window.iceServers,
        peers: [],
        signalingChannel: null,
        localStream: null,
        ready: false,
        pendingPeers: [],
        pendingMessages: [],
        createdAt: 0,
        recorder: {
            id: null,
            connection: null
        }
    }),
    computed: {
        cells(){
            return this.peers.length + 1;
        }
    },
    methods: {
        initConfirm(){
            this.initSignalingChannel();
            $('#init-confirm').modal('hide');
        },
        setLocalPeerReady(){
            this.ready = true;
            for(var i in this.pendingPeers)
                this.peers.push(this.pendingPeers[i]);
            this.pendingPeers = [];
            setTimeout(() => {
                for(var i in this.pendingMessages)
                    this.handleSignalingMessage(this.pendingMessages[i]);
                this.pendingMessages = [];
            }, 1000);
        },
        initSignalingChannel(){
            this.signalingChannel = new WebSocket(baseUrl.replace(/^https?/, 'wss') + '/websocket');
            this.signalingChannel.onopen = () => {
                this.signalingChannel.send(JSON.stringify({
                    action: 'init',
                    code: this.roomId
                }));
            };
            this.addSignalingListeners();
        },
        addSignalingListeners(){
            this.signalingChannel.onmessage = (e) => {
                var message = JSON.parse(e.data);
                if(message.action == 'init'){
                    this.roomId = message.code;
                    this.$nextTick(() => {
                        if(message.local){
                            this.createdAt = message.time;
                            this.$refs.qr.initQrButtons();
                        }
                        this.initPeer(message.id, message.local, message.time);
                    });
                } else if(this.ready)
                    this.handleSignalingMessage(message);
                else
                    this.pendingMessages.push(message);
            }
        },
        initPeer(peerId, local, time, recording){
            var peer = {
                id: peerId,
                local: local,
                time: time,
                recording: recording || false
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
                case 'record':
                    this.initPeer(message.senderId, false, 0, true);
                    break;
                case 'close':
                    var peerIndex = _findIndex(this.peers, (peer) => peer.id == message.id);
                    if(peerIndex != -1)
                        this.peers.splice(peerIndex, 1);
            }
        }
    },
    mounted(){
        this.$refs.initConfirm.show();
    }
});

for(var component in libraries)
    app.component(component, libraries[component]);
app.mount('#app');
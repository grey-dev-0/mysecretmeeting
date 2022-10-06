<template>
    <div class="col-12 col-md" :id="id">
        <div class="card">
            <div class="card-body row">
                <template v-if="stream != null">
                    <video v-if="local" class="col" autoplay muted></video>
                    <video v-else="local" class="col" autoplay></video>
                </template>
                <h3 v-else-if="error != null" class="col text-center error"></h3>
                <p class="col loading" v-else>Loading..</p>
            </div>
        </div>
    </div>
</template>

<script>
var $ = window.$;
export default {
    name: 'Peer',
    props: {
        id: {
            type: Number,
            required: true
        },
        local: {
            type: Boolean,
            required: true
        },
        createdAt: {
            type: Number,
            default: 0
        }
    },
    data(){
        return {
            stream: null,
            error: null,
            connection: null,
            pendingSdp: null,
            pendingCandidates: [],
            host: hostPeer
        };
    },
    methods: {
        initLocalStream(){
            var peer = this;
            navigator.mediaDevices.getUserMedia({video: true, audio: true}).then(function(stream){
                peer.stream = stream;
                peer.$root.localStream = peer.stream;
                peer.$nextTick(function(){
                    $('#' + peer.id).find('video')[0].srcObject = peer.stream;
                    peer.$root.setLocalPeerReady();
                });
            }).catch(function(error){
                peer.error = error;
            });
        },
        initRemoteStream(){
            var peer = this;
            if(!this.sendLocalStream())
                var localStreamSent = setInterval(function(){
                    if(peer.sendLocalStream())
                        clearInterval(localStreamSent);
                }, 100);
            this.stream = new MediaStream();
            this.connection.addEventListener('track', function(e){
                var peerVideo = $('#' + peer.id).find('video')[0];
                peer.stream.addTrack(e.track);
                if(!peerVideo.srcObject)
                    peerVideo.srcObject = peer.stream;
                console.log('Remote Track Added', e.track);
            });
        },
        sendLocalStream(){
            var peer = this;
            if(this.$root.localStream != null){
                this.$root.localStream.getTracks().forEach(function(track){
                    peer.connection.addTrack(track);
                    console.log('Local Track Added', track);
                });
                return true;
            } else
                return false;
        },
        initRemotePeer(){
            this.connection = new RTCPeerConnection({
                iceServers: this.$root.iceServers
            });
            this.initRemoteStream();
            this.addIceListeners();
            if(this.createdAt > this.$root.createdAt){
                var peer = this;
                this.connection.createOffer().then(function(offer){
                    return peer.connection.setLocalDescription(offer);
                }).then(function(){
                    peer.pendingSdp = {
                        action: 'offer',
                        id: peer.id,
                        offer: peer.connection.localDescription
                    };
                });
            }
        },
        addIceListeners(){
            var peer = this;
            this.connection.addEventListener('icecandidate', function(e){
                if(e.candidate)
                    peer.$root.signalingChannel.send(JSON.stringify({
                        action: 'candidate',
                        id: peer.id,
                        candidate: e.candidate
                    }));
            });
            this.connection.addEventListener('icecandidateerror', function(e){
                console.error('ICE error:', e);
            });
            this.connection.addEventListener('icegatheringstatechange', function(e){
                var connection = e.target;
                if(connection.iceGatheringState == 'complete' && peer.pendingSdp != null){
                    peer.$root.signalingChannel.send(JSON.stringify(peer.pendingSdp));
                    peer.pendingSdp = null;
                }
            });
        },
        handleOffer(offer, senderId){
            var peer = this;
            this.connection.setRemoteDescription(new RTCSessionDescription(offer)).then(function(){
                peer.addPendingCandidates();
                return peer.connection.createAnswer();
            }).then(function(answer){
                return peer.connection.setLocalDescription(answer);
            }).then(function(){
                peer.pendingSdp = {
                    action: 'answer',
                    id: senderId,
                    answer: peer.connection.localDescription
                };
            });
        },
        handleAnswer(answer){
            if(!this.connection.remoteDescription){
                var peer = this;
                this.connection.setRemoteDescription(new RTCSessionDescription(answer)).then(function(){
                    peer.addPendingCandidates();
                });
            }
        },
        addPendingCandidates(){
            var peer = this;
            this.pendingCandidates.forEach(function(candidate){
                peer.handleCandidate(candidate);
            });
            this.pendingCandidates = [];
        },
        handleCandidate(candidate){
            if(!this.connection.remoteDescription)
                this.pendingCandidates.push(candidate);
            else
                this.connection.addIceCandidate(new RTCIceCandidate(candidate)).catch(function(e){
                    console.error('Could not add received ICE candidate', e);
                });
        }
    },
    mounted(){
        this.$nextTick(function(){
            if(this.local)
                this.initLocalStream();
            else
                this.initRemotePeer();
        });
    }
}
</script>

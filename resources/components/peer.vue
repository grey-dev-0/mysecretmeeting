<template>
    <div class="col-12 col-md mt-2" v-if="!recording" :id="id">
        <div class="card peer">
            <div class="card-body row">
                <template v-if="stream != null">
                    <template v-if="!audioOnly">
                        <video v-if="local" class="col" autoplay muted></video>
                        <video v-else class="col" autoplay></video>
                    </template>
                    <div v-else class="audio-container">
                        <audio v-if="local" class="d-none" autoplay muted></audio>
                        <audio v-else class="d-none" autoplay></audio>
                        <div class="audio-note d-inline-block">Audio Only</div>
                    </div>
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
        },
        recording: {
            required: false
        },
        audioOnly: {
            type: Boolean,
            default: false
        }
    },
    data: () => ({
        stream: null,
        error: null,
        connection: null,
        pendingSdp: null,
        pendingCandidates: [],
        host: window.hostPeer
    }),
    methods: {
        initLocalStream(){
            navigator.mediaDevices.getUserMedia({
                video: !this.audioOnly,
                audio: true
            }).then((stream) => {
                this.stream = stream;
                this.$root.localStream = this.stream;
                this.$nextTick(() => {
                    $('#' + this.id).find(this.audioOnly? 'audio' : 'video')[0].srcObject = this.stream;
                    this.$root.setLocalPeerReady();
                });
            }).catch((error) => {
                this.error = error;
            });
        },
        initRemoteStream(){
            if(!this.sendLocalStream())
                var localStreamSent = setInterval(() => {
                    if(this.sendLocalStream())
                        clearInterval(localStreamSent);
                }, 100);
            if(this.recording)
                return;
            this.stream = new MediaStream();
            this.connection.addEventListener('track', (e) => {
                var peerMedia = $('#' + this.id).find((this.audioOnly)? 'audio' : 'video')[0];
                this.stream.addTrack(e.track);
                if(!peerMedia.srcObject)
                    peerMedia.srcObject = this.stream;
                console.log('Remote Track Added', e.track);
            });
        },
        sendLocalStream(){
            if(this.$root.localStream != null){
                this.$root.localStream.getTracks().forEach((track) => {
                    this.connection.addTrack(track);
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
            if(this.recording)
                this.handleOffer(this.recording, this.id)
            else if(this.createdAt > this.$root.createdAt){
                this.connection.createOffer().then((offer) => this.connection.setLocalDescription(offer)).then(() => {
                    this.pendingSdp = {
                        action: 'offer',
                        id: this.id,
                        offer: this.connection.localDescription
                    };
                });
            }
        },
        addIceListeners(){
            this.connection.addEventListener('icecandidate', (e) => {
                if(e.candidate)
                    this.$root.signalingChannel.send(JSON.stringify({
                        action: 'candidate',
                        id: this.id,
                        candidate: e.candidate
                    }));
            });
            this.connection.addEventListener('icecandidateerror', (e) => {
                console.error('ICE error:', e);
            });
            this.connection.addEventListener('icegatheringstatechange', (e) => {
                var connection = e.target;
                if(connection.iceGatheringState == 'complete' && this.pendingSdp != null){
                    this.$root.signalingChannel.send(JSON.stringify(this.pendingSdp));
                    this.pendingSdp = null;
                }
            });
        },
        handleOffer(offer, senderId){
            this.connection.setRemoteDescription(new RTCSessionDescription(offer)).then(() => {
                this.addPendingCandidates();
                return this.connection.createAnswer();
            }).then((answer) => {
                return this.connection.setLocalDescription(answer);
            }).then(() => {
                this.pendingSdp = {
                    action: 'answer',
                    id: senderId,
                    answer: this.connection.localDescription
                };
            });
        },
        handleAnswer(answer){
            if(!this.connection.remoteDescription){
                this.connection.setRemoteDescription(new RTCSessionDescription(answer)).then(() => this.addPendingCandidates());
            }
        },
        addPendingCandidates(){
            this.pendingCandidates.forEach((candidate) => this.handleCandidate(candidate));
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
            if(!this.recording)
                $('html, body').animate({
                    scrollTop: $(document).height()
                }, 1000);
        });
    }
}
</script>

<style lang="scss">
.peer{
    video{
        transform: rotateY(180deg);
    }
}

.audio-container{
    position: relative;
    width: 100%;
    padding-top: 50%;

    .audio-note{
        position: absolute;
        margin: auto;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        font-size: 1.5rem;
        font-weight: bolder;
        width: 100px;
        height: 100px;
        text-align: center;
    }
}
</style>
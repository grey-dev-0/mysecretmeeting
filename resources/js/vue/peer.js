(function(){
    Vue.component('peer', {
        name: 'Peer',
        template: '#peer',
        props: {
            id: {
                type: Number,
                required: true
            },
            local: {
                type: Boolean,
                required: true
            }
        },
        data: function(){
            return {
                stream: null,
                error: null,
                connection: null,
                host: hostPeer
            };
        },
        mounted: function(){
            this.$nextTick(function(){
                if(this.local)
                    this.initLocalStream();
                else
                    this.initRemotePeer();
            });
        },
        methods: {
            initLocalStream: function(){
                var peer = this;
                navigator.mediaDevices.getUserMedia({video: true, audio: true}).then(function(stream){
                    peer.stream = stream;
                    peer.$root.localStream = peer.stream;
                    peer.$nextTick(function(){
                        $('#' + peer.id).find('video')[0].srcObject = peer.stream;
                    });
                }).catch(function(error){
                    peer.error = error;
                });
            },
            initRemoteStream: function(){
                var peer = this;
                if(!this.sendLocalStream())
                    var localStreamSent = setInterval(function(){
                        if(peer.sendLocalStream())
                            clearInterval(localStreamSent);
                    }, 100);
                this.stream = new MediaStream();
                this.connection.addEventListener('track', function(e){
                    peer.stream.addTrack(e.track);
                    console.log('Remote Track Added', e.track);
                });
                this.$nextTick(function(){
                    $('#' + this.id).find('video')[0].srcObject = this.stream;
                });
            },
            sendLocalStream: function(){
                var peer = this;
                if(this.$root.localStream != null){
                    this.$root.localStream.getTracks().forEach(function(track){
                        peer.connection.addTrack(track);
                        console.log('Local Track Added', track);
                    });
                    return true;
                } else
                    return  false;
            },
            initRemotePeer: function(){
                this.connection = new RTCPeerConnection({
                    iceServers: this.$root.iceServers
                });
                this.initRemoteStream();
                this.addIceListeners();
                if(this.host){
                    var peer = this;
                    this.connection.createOffer().then(function(offer){
                        peer.connection.setLocalDescription(offer);
                        peer.$root.signalingChannel.send(JSON.stringify({
                            action: 'offer',
                            id: peer.id,
                            offer: offer
                        }));
                    });
                }
            },
            addIceListeners: function(){
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
            },
            handleOffer: function(offer, senderId){
                this.connection.setRemoteDescription(new RTCSessionDescription(offer));
                var peer = this;
                this.connection.createAnswer().then(function(answer){
                    peer.connection.setLocalDescription(answer);
                    peer.$root.signalingChannel.send(JSON.stringify({
                        action: 'answer',
                        id: senderId,
                        answer: answer
                    }));
                })
            },
            handleAnswer: function(answer){
                this.connection.setRemoteDescription(new RTCSessionDescription(answer));
            },
            handleCandidate: function(candidate){
                this.connection.addIceCandidate(new RTCIceCandidate(candidate)).catch(function(e){
                    console.error('Could not add received ICE candidate', e);
                })
            }
        }
    });
})();

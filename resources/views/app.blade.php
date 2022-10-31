<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My Secret Meting</title>
    <link rel="stylesheet" href="{{asset(mix('css/app.css'))}}">
    <script src="{{asset(mix('js/manifest.js'))}}"></script>
    <script src="{{asset('js/vendors.js')}}"></script>
    <script src="{{asset('js/vue.min.js')}}"></script>
</head>
<body>
<div id="app" class="container-fluid" v-cloak>
    <div class="row mb-2">
        <qr ref="qr" :code="roomId"></qr>
        <template v-for="(peer, i) in peers">
            <peer :ref="'p-'+peer.id" :id="peer.id" :local="peer.local" :created-at="peer.time"
                  :recording="peer.recording" :audio-only="peer.audioOnly"></peer>
            <div class="w-100" v-if="i > 0 && (i + 2) % 4 == 0"></div>
        </template>
    </div>
    <modal id="init-confirm" color="warning" ref="initConfirm" static>
        <template #header>Warning</template>
        <p>This application will automatically stream your microphone and camera to other participants existing in the requested room when you proceed.</p>
        <p>If you haven't requested a particular room by its QR, you can share the generated QR code to people you'd like to have video chat with.</p>
        <p @if(!env('RECORD_CALLS')) class="mb-0" @endif>In both cases please make sure that it's appropriate to share your captured video to others before you proceed.</p>
        @if(env('RECORD_CALLS'))
            <p><strong>This room will be recorded for testing the streaming and recording functionalities so, please ensure that you DONT disclose any private information during this session.</strong></p>
        @endif
        <template #footer>
            <div class="btn btn-warning" @click="initConfirm">Proceed</div>
            <div class="btn btn-outline-secondary" data-dismiss="modal">Decline</div>
        </template>
    </modal>
</div>
<div id="app-loader" class="text-muted">..Loading..</div>
<script>
    var baseUrl = '{{url('/')}}';
    var qrCode = '{{$qrCode}}';
    var hostPeer = qrCode == '';
    var iceServers = @json($iceServers);
    var audioOnly = {{env('AUDIO_ONLY', false)? 'true' : 'false'}};
</script>
<script src="{{asset(mix('js/app.js'))}}"></script>
</body>
</html>

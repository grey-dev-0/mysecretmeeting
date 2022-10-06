<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My Secret Meting</title>
    <link rel="stylesheet" href="{{asset(mix('resources/css/app.css'))}}">
    <script src="{{asset('resources/js/vendors.js')}}"></script>
    <script src="{{asset('resources/js/vue.min.js')}}"></script>
</head>
<body>
<div id="app" class="container-fluid">
    <div class="row">
        <qr ref="qr" :code="roomId"></qr>
        <template v-for="(peer, i) in peers">
            <peer :ref="'p-'+peer.id" :id="peer.id" :local="peer.local" :created-at="peer.time"></peer>
            <div class="w-100" v-if="i > 0 && (i + 2) % 4 == 0"></div>
        </template>
    </div>
</div>
<script type="text/javascript">
    var baseUrl = '{{url('/')}}';
    var qrCode = '{{$qrCode}}';
    var hostPeer = qrCode == '';
    var iceServers = @json($iceServers->toJson());
</script>
<script src="{{asset(mix('resources/js/app.js'))}}"></script>
</body>
</html>

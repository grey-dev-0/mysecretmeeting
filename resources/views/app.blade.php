<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My Secret Meeting</title>
    <script type="text/javascript" src="{{asset('resources/js/jquery.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/js/bootstrap.bundle.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/js/vue.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/js/clipboard.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/js/app.min.js')}}"></script>
    <link rel="stylesheet" href="{{asset('resources/css/bootstrap.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('resources/css/app.min.css')}}" type="text/css">
</head>
<body>
<div class="container-fluid">
    <div id="app" class="row">
        <template v-for="(connection, id, index) in connections">
            <div :class="(id == 'qr_code')? 'col-xs-6 col-sm-6 col-md-3 col-lg-2' : 'col'">
                <div class="card">
                    <div class="card-body" v-if="id == 'qr_code'">
                        <img class="img-fluid" id="qr-code" :src="(connections.qr_code !== null)? ('{{url('qr')}}?c='+connections.qr_code) : '{{asset('resources/img/no-qr.png')}}'" alt="QR Code">
                        <div id="copy-url" class="btn btn-outline-primary btn-block" :data-clipboard-text="'{{url('/')}}?c='+connections.qr_code">Copy QR Link</div>
                        <div id="upload-qr" class="btn btn-outline-info btn-block">Upload QR</div>
                        <input type="file" class="d-none" name="qr_upload">
                        <div id="capture-qr" class="btn btn-outline-secondary btn-block">Capture QR</div>
                        <input type="file" accept="image/*;capture=camera" class="d-none">
                    </div>
                    <div class="card-body row" v-if="id == 'my_connection'">
                        <video class="col" autoplay muted v-if="connection.stream !== null" ref="my_video"></video>
                        <h3 class="text-center w-100" v-if="connection.error !== null">@{{connection.error}}</h3>
                    </div>
                    <div class="card-body row" v-else>
                        <video src="" class="col" autoplay v-if="connection.stream !== null" ref="id"></video>
                        <h3 class="text-center w-100" v-if="connection.error !== null">@{{connection.error}}</h3>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script type="text/javascript">
    var initCode = '{{request('c', '')}}';
    $(document).ready(function(){
        rtc.init('{{str_replace(['http://', 'https://'], '', url('/'))}}');
    });
</script>
</body>
</html>
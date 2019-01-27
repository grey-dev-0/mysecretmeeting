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
    <script type="text/javascript" src="{{asset('resources/js/clipboard.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('resources/js/app.min.js')}}"></script>
    <link rel="stylesheet" href="{{asset('resources/css/bootstrap.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('resources/css/app.min.css')}}" type="text/css">
</head>
<body>
<div class="container-fluid">
    <div id="app" class="row">
        <div class="col-xs-6 col-sm-6 col-md-3 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <img class="img-fluid" id="qr-code" src="" alt="QR Code">
                    <div id="copy-url" class="btn btn-outline-primary btn-block" data-clipboard-text="'{{url('/')}}">Copy QR Link</div>
                    <div id="capture-qr" class="btn btn-outline-info btn-block">Capture QR</div>
                    <input type="file" accept="image/*;capture=camera" class="d-none" name="qr_image">
                </div>
            </div>
        </div>
        <div class="col" id="my-connection">
            <div class="card">
                <div class="card-body row">
                    <video class="col d-none" autoplay muted></video>
                    <h3 class="d-none w-100 text-center error"></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var initCode = '{{request('c', '')}}';
    var qrPlaceholder = '{{asset('resources/img/no-qr.png')}}';
    $(document).ready(function(){
        rtc.init('{{str_replace(['http://', 'https://'], '', url('/'))}}');
    });
</script>
</body>
</html>
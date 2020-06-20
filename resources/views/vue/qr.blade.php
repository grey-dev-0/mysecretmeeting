<script id="qr" type="text/x-template">
    <div class="col-sm-6 col-md-3 col-lg-2">
        <div class="card">
            <div class="card-body">
                <img class="img-fluid" :src="codeImage || '{{asset('resources/img/no-qr.png')}}'" alt="Qr Code">
                <div id="copy-url" class="btn btn-outline-primary btn-block" data-clipboard-text="">Copy QR Link</div>
                <div id="capture-qr" class="btn btn-outline-info btn-block">Capture QR</div>
                <input type="file" accept="image/*;capture=camera" class="d-none" name="qr_image">
            </div>
        </div>
    </div>
</script>
<script src="{{asset('resources/js/vue/qr.min.js')}}"></script>

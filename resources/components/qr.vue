<template>
    <div class="col-sm-6 col-md-3 col-lg-2 mt-2">
        <div class="card">
            <div class="card-body">
                <img class="img-fluid" :src="codeImage || baseUrl + '/img/no-qr.png'" alt="Qr Code">
                <div id="copy-url" class="btn btn-outline-primary btn-block" data-clipboard-text="">Copy QR Link</div>
                <div id="capture-qr" class="btn btn-outline-info btn-block">Capture QR</div>
                <input type="file" accept="image/*;capture=camera" class="d-none" name="qr_image">
            </div>
        </div>
    </div>
</template>

<script>
var $ = window.$;
var ClipboardJS = window.ClipboardJS;
export default {
    name: 'QrCode',
    props: {
        code: {
            type: String
        }
    },
    data: () => ({
        baseUrl: window.baseUrl
    }),
    computed: {
        codeImage(){
            return (this.code == '')? null : (baseUrl + '/qr?c=' + this.code);
        }
    },
    methods: {
        initQrButtons(){
            $('#copy-url').attr('data-clipboard-text', baseUrl + '/?c=' + this.code);
            new ClipboardJS('#copy-url');
            $('#capture-qr').on('click', function(){
                $(this).next().trigger('click');
            }).next().on('change', function(){
                // TODO: run ajax to join the given QR room if exists.
            });
        }
    }
}
</script>

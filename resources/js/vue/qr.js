(function(){
    Vue.component('qr', {
        name: 'QrCode',
        template: '#qr',
        props: {
            code: {
                type: String
            }
        },
        computed: {
            codeImage: function(){
                return (this.code == '')? null : (baseUrl + '/qr?c=' + this.code);
            }
        },
        mounted: function(){
            this.$nextTick(function(){
                this.$root.initSignalingChannel(this.code);
            });
        },
        methods: {
            initQrButtons: function(){
                $('#copy-url').attr('data-clipboard-text', baseUrl + '/?c=' + this.code);
                new ClipboardJS('#copy-url');
                $('#capture-qr').on('click', function(){
                    $(this).next().trigger('click');
                }).next().on('change', function(){
                    // TODO: run ajax to join the given QR room if exists.
                });
            }
        }
    });
})();

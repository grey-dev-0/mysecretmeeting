(function(Vue, $, ClipboardJS){
    $(document).ready(function(){
        window.rtc = new Vue({
            el: '#app',
            data: {
                connections: {
                    qr_code: null
                },
                channels: {},
                websocket: null
            },
            methods: {
                init: function(domain){
                    navigator.getUserMedia = navigator.getUserMedia ||
                        navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
                    this.websocket = new WebSocket('ws://' + domain + '/websocket');
                    this.websocket.onopen = function(){
                        rtc.websocket.send(JSON.stringify({
                            action: 'init'
                        }));
                    };
                    this.websocket.onmessage = function(e){
                        var message = JSON.parse(e.data);
                        switch(message.action){
                            case 'init':
                                rtc.connections.qr_code = message.qr_code;
                                new ClipboardJS('#copy-url');
                                break;
                        }
                    }
                }
            }
        });

        $('body').on('click', '#upload-qr, #capture-qr', function(){
            $(this).next('input').trigger('click');
        });
    });
})(Vue, jQuery, ClipboardJS);
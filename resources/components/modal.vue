<template>
    <div :class="modalClass" :id="id" :data-backdrop="(this.static)? 'static' : null" :data-keyboard="(this.static)? 'false' : null" :style="zIndex? ('z-index: ' + (1050 + zIndex * 10)) : null">
        <div :class="dialogClass">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><slot name="header"></slot></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div :class="bodyClass">
                    <slot></slot>
                </div>
                <div v-if="!!$slots.footer" class="modal-footer">
                    <slot name="footer"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
var $ = window.$;

export default {
    name: "Modal",
    props: {
        id: {
            type: String,
            required: true
        },
        size: String,
        'static': {
            type: Boolean,
            default: false
        },
        color: String,
        noPadding: {
            type: Boolean,
            default: false
        },
        zIndex: Number
    },
    computed: {
        modalClass: function(){
            var theClass = 'modal fade';
            if(this.color)
                theClass += ' modal-' + this.color;
            return theClass;
        },
        dialogClass: function(){
            var theClass = 'modal-dialog';
            if(this.size)
                theClass += ' modal-' + this.size;
            return theClass;
        },
        bodyClass: function(){
            var theClass = 'modal-body';
            if(this.noPadding)
                theClass += ' p-0';
            return theClass;
        }
    },
    methods: {
        show: function(reset){
            if(reset !== undefined)
                reset();
            $('#' + this.id).modal('show');
        }
    },
    mounted(){
        if(this.zIndex)
            $('body').on('show.bs.modal', '#' + this.id, () => {
                setTimeout(() => {
                    $('.modal-backdrop.show:last-of-type').css('z-index', 1050 + this.zIndex * 10 - 1);
                }, 100);
            });
    }
}
</script>

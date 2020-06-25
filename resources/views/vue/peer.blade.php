<script id="peer" type="text/x-template">
    <div class="col" :id="id">
        <div class="card">
            <div class="card-body row">
                <template v-if="stream != null">
                    <video v-if="local" class="col" autoplay muted></video>
                    <video v-else="local" class="col" autoplay></video>
                </template>
                <h3 v-else-if="error != null" class="col text-center error"></h3>
                <p class="col loading" v-else>Loading..</p>
            </div>
        </div>
    </div>
</script>
<script src="{{asset('resources/js/vue/peer.min.js')}}?v=a1.2p"></script>

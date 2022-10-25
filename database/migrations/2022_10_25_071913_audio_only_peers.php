<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::table('peers', function(Blueprint $table){
            $table->boolean('audio_only')->default(false)->after('room_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::table('peers', function(Blueprint $table){
            $table->dropColumn('audio_only');
        });
    }
};

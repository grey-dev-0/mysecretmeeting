<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppTables extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('rooms', function(Blueprint $table){
            $table->string('id', 40)->primary();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('peers', function(Blueprint $table){
            $table->unsignedInteger('id')->primary();
            $table->string('room_id', 40);
            $table->foreign('room_id')->references('id')->on('rooms')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ice_servers', function(Blueprint $table){
            $table->increments('id');
            $table->string('url');
            $table->tinyInteger('type');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        Schema::create('ice_credentials', function(Blueprint $table){
            $table->unsignedInteger('id')->primary();
            $table->foreign('id')->references('id')->on('ice_servers')->onUpdate('cascade')->onDelete('cascade');
            $table->string('username');
            $table->string('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('ice_credentials');
        Schema::dropIfExists('ice_servers');
        Schema::dropIfExists('peers');
        Schema::dropIfExists('rooms');
    }
}

<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peer extends Model{
    public $timestamps = false;
    public $incrementing = false;
    protected $casts = ['audio_only' => 'boolean'];
    protected $dates = ['created_at'];
    protected $guarded = [];

    public function room(){
        return $this->belongsTo(Room::class);
    }
}

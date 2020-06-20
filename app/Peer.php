<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Peer extends Model{
    public $timestamps = false;
    public $incrementing = false;
    protected $dates = ['created_at'];
    protected $guarded = [];

    public function room(){
        return $this->belongsTo(Room::class);
    }
}

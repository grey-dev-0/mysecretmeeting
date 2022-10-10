<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model{
    public $timestamps = false;
    public $incrementing = false;
    protected $dates = ['created_at'];
    protected $guarded = [];

    public function peers(){
        return $this->hasMany(Peer::class);
    }
}

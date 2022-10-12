<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IceCredential extends Model{
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function server(){
        return $this->belongsTo(IceServer::class, 'id');
    }
}

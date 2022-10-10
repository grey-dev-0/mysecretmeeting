<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IceServer extends Model{
    protected $guarded = ['id'];
    protected $hidden = ['type'];

    public function getUrlAttribute($url){
        $prefix = $this->type == 1? 'stun' : 'turn';
        return "$prefix:$url";
    }
}

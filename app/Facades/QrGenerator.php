<?php namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class QrGenerator extends Facade{
    protected static function getFacadeAccessor(){
        return 'qr-generator';
    }
}
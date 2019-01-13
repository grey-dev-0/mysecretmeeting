<?php

namespace App\Providers;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(){
        $this->app->singleton('qr-generator', function(){
            $renderer = new ImageRenderer(
                new RendererStyle(config('services.qr-generator.width')),
                new ImagickImageBackEnd()
            );
            return new Writer($renderer);
        });
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-27
 * Time: 16:56
 */

namespace Boxiaozhi\Cwiz;


use Illuminate\Support\ServiceProvider;

class CwizServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/cwiz.php' => config_path('cwiz.php')
        ], 'config');
    }

    public function register()
    {
        $this->app->singleton('cwiz', function(){
            return new Cwiz();
        });
    }
}
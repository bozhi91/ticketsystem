<?php

namespace App\Providers;

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
        \Validator::extend('!exists', function($attribute, $value, $parameters, $validator)
        {
            $exists = \DB::table($parameters[0])
                    ->where($parameters[1], '=', $value)
                    ->where($parameters[2], '=', $parameters[3])
                    ->count() > 0;

            return !$exists;
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

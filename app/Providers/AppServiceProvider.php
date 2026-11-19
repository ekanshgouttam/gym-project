<?php

namespace App\Providers;

use App\Services\Sms\LogSmsSender;
use App\Services\Sms\Msg91SmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(SmsSender::class, function () {
            return config('services.sms.driver') === 'msg91' ? new Msg91SmsSender() : new LogSmsSender();
        });
    }

    public function boot()
    {
        //
    }
}

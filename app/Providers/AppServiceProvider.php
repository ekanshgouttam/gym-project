<?php

namespace App\Providers;

use App\Services\Sms\LogSmsSender;
use App\Services\Sms\Msg91SmsSender;
use App\Services\Sms\SmartPingSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(SmsSender::class, function () {
            switch (config('services.sms.driver')) {
                case 'msg91': return new Msg91SmsSender();
                case 'smartping': return new SmartPingSmsSender();
                default: return new LogSmsSender();
            }
        });
    }

    public function boot()
    {
        //
    }
}

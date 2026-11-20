<?php

namespace Tests\Feature;

use App\Services\Sms\SmartPingSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class SmartPingSmsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sms' => array_merge(config('services.sms'), [
            'driver' => 'smartping', 'smartping_username' => 'user.trans', 'smartping_password' => 'pw', 'smartping_sender' => 'ABCDEF',
        ])]);
    }

    public function test_sends_the_otp_through_smartping()
    {
        Http::fake(['pgapi.smartping.io/*' => Http::response(['statusCode' => 200, 'state' => 'SUBMIT_ACCEPTED'])]);

        $sender = app(SmsSender::class);
        $this->assertInstanceOf(SmartPingSmsSender::class, $sender);
        $sender->sendOtp('+919820012345', '482913');

        Http::assertSent(function (Request $request) {
            return Str::startsWith($request->url(), 'https://pgapi.smartping.io/fe/api/v1/send?')
                && $request['username'] === 'user.trans'
                && $request['from'] === 'ABCDEF'
                && $request['to'] === '919820012345'
                && $request['text'] === 'Your OTP for verification is 482913 It is valid for 10 minutes. Do not share this code with anyone. PAM FINCAP';
        });
    }

    public function test_a_rejected_message_is_reported_as_a_failure()
    {
        Http::fake(['pgapi.smartping.io/*' => Http::response(['statusCode' => 400, 'state' => 'REJECTED', 'description' => 'Invalid template'])]);

        $this->expectException(RuntimeException::class);
        app(SmsSender::class)->sendOtp('+919820012345', '482913');
    }
}

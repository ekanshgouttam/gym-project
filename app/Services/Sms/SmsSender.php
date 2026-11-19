<?php

namespace App\Services\Sms;

interface SmsSender
{
    /** Deliver a one-time code to an E.164 phone number (e.g. +919820000001). */
    public function sendOtp(string $phone, string $code): void;
}

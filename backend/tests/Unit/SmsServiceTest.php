<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    public function test_it_logs_instead_of_sending_when_twilio_is_not_configured(): void
    {
        Config::set('services.twilio.sid', null);
        Config::set('services.twilio.auth_token', null);
        Config::set('services.twilio.from', null);
        Log::spy();

        (new SmsService)->send('+15551234567', 'Your membership expires soon.');

        Log::shouldHaveReceived('info')->once()->with('[SMS stub] would send to +15551234567: Your membership expires soon.');
    }
}

<?php

namespace Tests\Feature\Console;

use App\Mail\RenewalReminderMail;
use App\Models\Member;
use App\Models\Membership;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\FakesFirebase;
use Tests\TestCase;

class SendRenewalRemindersTest extends TestCase
{
    use FakesFirebase, RefreshDatabase;

    protected MockInterface $sms;

    protected function setUp(): void
    {
        parent::setUp();

        // Spied (not stubbed) so no test ever depends on real Twilio credentials/network,
        // even ones that don't assert on SMS directly — see fithub_notification_credentials memory.
        $this->sms = Mockery::spy(SmsService::class);
        $this->app->instance(SmsService::class, $this->sms);
    }

    public function test_it_notifies_members_expiring_within_three_days_and_marks_them_sent(): void
    {
        Mail::fake();
        $messaging = $this->fakeFirebaseMessaging();

        $member = Member::factory()->create(['fcm_token' => 'token-abc']);
        $membership = Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(2)->toDateString(),
            'renewal_reminder_sent_at' => null,
        ]);

        $this->artisan('app:send-renewal-reminders')->assertSuccessful();

        Mail::assertSent(RenewalReminderMail::class, fn ($mail) => $mail->membership->is($membership) && $mail->hasTo($member->email));
        $messaging->shouldHaveReceived('send')->once();
        $this->assertNotNull($membership->fresh()->renewal_reminder_sent_at);
    }

    public function test_it_sends_an_sms_stub_when_the_member_has_a_phone_number(): void
    {
        Mail::fake();
        $this->fakeFirebaseMessaging();

        $member = Member::factory()->create(['phone' => '+15551234567']);
        Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        $this->artisan('app:send-renewal-reminders')->assertSuccessful();

        $this->sms->shouldHaveReceived('send')->once()->with('+15551234567', Mockery::type('string'));
    }

    public function test_it_skips_memberships_already_reminded(): void
    {
        Mail::fake();
        $messaging = $this->fakeFirebaseMessaging();

        Member::factory()->create(['fcm_token' => 'token-abc'])
            ->memberships()->save(Membership::factory()->make([
                'end_date' => now()->addDays(2)->toDateString(),
                'renewal_reminder_sent_at' => now()->subHour(),
            ]));

        $this->artisan('app:send-renewal-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $messaging->shouldNotHaveReceived('send');
    }

    public function test_it_skips_memberships_expiring_outside_the_reminder_window(): void
    {
        Mail::fake();
        $messaging = $this->fakeFirebaseMessaging();

        $member = Member::factory()->create(['fcm_token' => 'token-abc']);
        Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->artisan('app:send-renewal-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $messaging->shouldNotHaveReceived('send');
    }

    public function test_it_still_emails_members_without_an_fcm_token_but_skips_the_push(): void
    {
        Mail::fake();
        $messaging = $this->fakeFirebaseMessaging();

        $member = Member::factory()->create(['fcm_token' => null]);
        $membership = Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(1)->toDateString(),
        ]);

        $this->artisan('app:send-renewal-reminders')->assertSuccessful();

        Mail::assertSent(RenewalReminderMail::class);
        $messaging->shouldNotHaveReceived('send');
        $this->assertNotNull($membership->fresh()->renewal_reminder_sent_at);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendProgressReminders extends Command
{
    protected $signature = 'app:send-progress-reminders';

    protected $description = 'Remind members who have not logged a measurement in the last 7 days to log their progress';

    public function handle(): void
    {
        $members = Member::query()
            ->whereNotNull('fcm_token')
            ->whereDoesntHave('measurements', function ($query) {
                $query->where('recorded_at', '>=', now()->subDays(7));
            })
            ->get();

        $messaging = Firebase::messaging();

        foreach ($members as $member) {
            $message = CloudMessage::new()
                ->withToken($member->fcm_token)
                ->withNotification(Notification::create(
                    'Log your progress',
                    'It has been a week — log your latest measurements to track your progress.'
                ));

            try {
                $messaging->send($message);
                $this->info("Progress reminder sent to member #{$member->id}");
            } catch (\Throwable $e) {
                $this->error("Failed to send progress reminder to member #{$member->id}: {$e->getMessage()}");
            }
        }
    }
}

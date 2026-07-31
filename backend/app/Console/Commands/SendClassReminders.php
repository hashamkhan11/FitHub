<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendClassReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-class-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify members whose booked class starts within the next hour';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $bookings = Booking::query()
            ->where('status', 'booked')
            ->whereNull('reminder_sent_at')
            ->whereHas('gymClass', function ($query) {
                $query->whereBetween('start_time', [now(), now()->addHour()]);
            })
            ->with(['gymClass', 'member'])
            ->get();

        $messaging = Firebase::messaging();

        foreach ($bookings as $booking) {
            if (! $booking->member->fcm_token) {
                continue;
            }

            $message = CloudMessage::new()
                ->withToken($booking->member->fcm_token)
                ->withNotification(Notification::create(
                    'Upcoming class reminder',
                    "{$booking->gymClass->name} starts at ".$booking->gymClass->start_time->format('g:i A')
                ))
                ->withData(['type' => 'class']);

            try {
                $messaging->send($message);
                $booking->update(['reminder_sent_at' => now()]);
                $this->info("Reminder sent for booking #{$booking->id}");
            } catch (\Throwable $e) {
                $this->error("Failed to send reminder for booking #{$booking->id}: {$e->getMessage()}");
            }
        }
    }
}

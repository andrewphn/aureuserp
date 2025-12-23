<?php

namespace Webkul\Lead\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webkul\Lead\Mail\NewLeadNotification;
use Webkul\Lead\Models\Lead;

class SendLeadNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notificationEmail = config('services.leads.notification_email')
            ?? config('mail.from.address')
            ?? 'leads@tcswoodwork.com';

        try {
            Mail::to($notificationEmail)
                ->send(new NewLeadNotification($this->lead));

            Log::info('Lead notification email sent', [
                'lead_id' => $this->lead->id,
                'email' => $notificationEmail,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send lead notification email', [
                'lead_id' => $this->lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

<?php

namespace Webkul\Lead\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Webkul\Lead\Models\Lead;

class NewLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Lead $lead
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "New Lead: {$this->lead->full_name}";

        if ($this->lead->project_type) {
            $subject .= " - {$this->lead->project_type}";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'leads::emails.new-lead-notification',
            with: [
                'lead' => $this->lead,
                'adminUrl' => $this->getAdminUrl(),
            ],
        );
    }

    /**
     * Get the URL to view the lead in admin
     */
    protected function getAdminUrl(): string
    {
        return url("/admin/leads/{$this->lead->id}");
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

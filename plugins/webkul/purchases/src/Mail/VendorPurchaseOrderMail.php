<?php

namespace Webkul\Purchase\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Vendor Purchase Order Mail class
 *
 */
class VendorPurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $message;

    public $pdfPath;

    /**
     * Create a new VendorPurchaseOrderMail instance
     *
     * @param mixed $subject
     * @param mixed $message
     * @param mixed $pdfPath
     */
    public function __construct($subject, $message, $pdfPath)
    {
        $this->subject = $subject;

        $this->message = $message;

        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    /**
     * Envelope
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    /**
     * Content
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'purchases::emails.index',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    /**
     * Attachments
     *
     * @return array
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->pdfPath),
        ];
    }
}

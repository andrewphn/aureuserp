<?php

namespace Webkul\Purchase\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VendorPurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $message;

    public $pdfPath;

    public function __construct($subject, $message, $pdfPath)
    {
        $this->subject = $subject;

        $this->message = $message;

        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->html($this->message)
            ->attach(Storage::disk('public')->path($this->pdfPath), [
                'mime' => 'application/pdf',
            ]);
    }
}

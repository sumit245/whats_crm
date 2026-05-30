<?php

namespace App\Mail;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QualityRatingAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Device $device,
        public readonly string $oldRating,
        public readonly string $newRating,
    ) {}

    public function envelope(): Envelope
    {
        $phone = $this->device->meta_profile['display_phone_number'] ?? $this->device->body;
        return new Envelope(
            subject: '[URGENT] WhatsApp Quality Rating Degraded: ' . $phone,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quality-rating-alert',
            with: [
                'device'    => $this->device,
                'oldRating' => $this->oldRating,
                'newRating' => $this->newRating,
                'phone'     => $this->device->meta_profile['display_phone_number'] ?? $this->device->body,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

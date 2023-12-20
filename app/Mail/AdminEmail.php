<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class AdminEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $podaci;

    /**
     * Create a new message instance.
     * (name, email, uri, title, note)
     */
    public function __construct($podaci)
    {
        $this->podaci = $podaci;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PRIMEDBA: '.$this->podaci['title'],
            cc: $this->podaci['email'],
            from: new Address($this->podaci['email'], $this->podaci['name']),
            replyTo: [
                new Address($this->podaci['email'], $this->podaci['name']),
            ],            
        );
    }

    /**
     * Get the message content definition.
     */
    
    public function content(): Content
    {
        return new Content(
            view: 'admin_email',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

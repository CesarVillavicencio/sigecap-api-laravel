<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class RegistrationConfirmationMail extends Mailable
{
    public function __construct(
        public Registration $registration,
        public string $qrPng
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de inscripción - SIGECAP'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-confirmation',
            with: [
                'registration' => $this->registration,
                'participant' => $this->registration->participant,
                'edition' => $this->registration->courseEdition,
                'course' => $this->registration->courseEdition->course,
                'qrPng' => $this->qrPng,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->qrPng,
                'qr-asistencia.png'
            )->withMime('image/png'),
        ];
    }
}

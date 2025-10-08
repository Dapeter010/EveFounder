<?php

namespace App\Mail;

use App\Models\User;
use App\Models\ProfileBoost;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoostActivated extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ProfileBoost $boost;

    public function __construct(User $user, ProfileBoost $boost)
    {
        $this->user = $user;
        $this->boost = $boost;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Profile Boost is Now Active - EveFound',
        );
    }

    public function content(): Content
    {
        $duration = match ($this->boost->boost_type) {
            'profile' => '30 minutes',
            'super' => '3 hours',
            'weekend' => 'the entire weekend',
            default => 'the boost period'
        };

        return new Content(
            markdown: 'emails.boost-activated',
            with: [
                'user' => $this->user,
                'boost' => $this->boost,
                'boostName' => ucfirst($this->boost->boost_type) . ' Boost',
                'duration' => $duration,
                'endsAt' => $this->boost->ends_at->format('l, F j, Y \a\t g:i A'),
                'amount' => '£' . number_format($this->boost->cost, 2),
            ],
        );
    }
}

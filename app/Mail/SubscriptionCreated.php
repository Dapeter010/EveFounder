<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Subscription $subscription;

    public function __construct(User $user, Subscription $subscription)
    {
        $this->user = $user;
        $this->subscription = $subscription;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . ucfirst($this->subscription->plan_type) . ' - EveFound',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-created',
            with: [
                'user' => $this->user,
                'subscription' => $this->subscription,
                'planName' => ucfirst($this->subscription->plan_type),
                'amount' => '£' . number_format($this->subscription->amount, 2),
                'nextBilling' => $this->subscription->ends_at->format('F j, Y'),
            ],
        );
    }
}

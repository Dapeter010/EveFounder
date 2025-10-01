<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $vapidPublicKey = env('VAPID_PUBLIC_KEY');
        $vapidPrivateKey = env('VAPID_PRIVATE_KEY');

        if ($vapidPublicKey && $vapidPrivateKey) {
            $auth = [
                'VAPID' => [
                    'subject' => env('APP_URL', 'https://evefound.com'),
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ];

            $this->webPush = new WebPush($auth);
        }
    }

    /**
     * Send push notification to a specific user
     */
    public function sendToUser(User $user, array $payload): bool
    {
        if (!isset($this->webPush)) {
            Log::warning('Push notifications not configured - VAPID keys missing');
            return false;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $sentCount = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh_key,
                'authToken' => $sub->auth_token,
            ]);

            try {
                $report = $this->webPush->sendOneNotification(
                    $subscription,
                    json_encode($payload)
                );

                if ($report->isSuccess()) {
                    $sentCount++;
                    $sub->update(['last_used_at' => now()]);
                } else {
                    // If subscription is expired or invalid, delete it
                    if ($report->isSubscriptionExpired()) {
                        $sub->delete();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Push notification failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sentCount > 0;
    }

    /**
     * Notify user about new message
     */
    public function notifyNewMessage(User $recipient, User $sender, string $messagePreview): void
    {
        $payload = [
            'title' => "{$sender->first_name} sent you a message",
            'body' => $messagePreview,
            'icon' => $sender->photos[0]->url ?? '/default-avatar.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'message-' . $sender->id,
            'data' => [
                'type' => 'new_message',
                'url' => '/messages/' . $sender->id,
                'sender_id' => $sender->id,
                'sender_name' => $sender->first_name,
            ],
            'requireInteraction' => true,
        ];

        $this->sendToUser($recipient, $payload);
    }

    /**
     * Notify user about new match
     */
    public function notifyNewMatch(User $user, User $match): void
    {
        $payload = [
            'title' => "It's a Match! 💕",
            'body' => "You and {$match->first_name} liked each other!",
            'icon' => $match->photos[0]->url ?? '/default-avatar.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'match-' . $match->id,
            'data' => [
                'type' => 'new_match',
                'url' => '/messages/' . $match->id,
                'match_id' => $match->id,
                'match_name' => $match->first_name,
            ],
            'requireInteraction' => true,
        ];

        $this->sendToUser($user, $payload);
    }

    /**
     * Notify user that their match is online
     */
    public function notifyMatchOnline(User $user, User $match): void
    {
        $payload = [
            'title' => "{$match->first_name} is online",
            'body' => "Say hello! 👋",
            'icon' => $match->photos[0]->url ?? '/default-avatar.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'online-' . $match->id,
            'data' => [
                'type' => 'match_online',
                'url' => '/messages/' . $match->id,
                'match_id' => $match->id,
                'match_name' => $match->first_name,
            ],
        ];

        $this->sendToUser($user, $payload);
    }

    /**
     * Notify user about new like received
     */
    public function notifyNewLike(User $user, User $liker): void
    {
        $payload = [
            'title' => 'Someone likes you! 😍',
            'body' => "You have a new like. Check who it is!",
            'icon' => '/icon-192x192.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'like-received',
            'data' => [
                'type' => 'new_like',
                'url' => '/likes',
            ],
        ];

        $this->sendToUser($user, $payload);
    }

    /**
     * Notify user about profile boost starting
     */
    public function notifyBoostStarted(User $user, string $boostType): void
    {
        $payload = [
            'title' => 'Your boost is now active! 🚀',
            'body' => "Your profile is being shown to more people.",
            'icon' => '/icon-192x192.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'boost-started',
            'data' => [
                'type' => 'boost_started',
                'url' => '/discover',
                'boost_type' => $boostType,
            ],
        ];

        $this->sendToUser($user, $payload);
    }

    /**
     * Notify user about profile boost ending soon
     */
    public function notifyBoostEndingSoon(User $user, int $minutesLeft): void
    {
        $payload = [
            'title' => 'Your boost is ending soon',
            'body' => "Only {$minutesLeft} minutes left. Make the most of it!",
            'icon' => '/icon-192x192.png',
            'badge' => '/badge-72x72.png',
            'tag' => 'boost-ending',
            'data' => [
                'type' => 'boost_ending_soon',
                'url' => '/discover',
                'minutes_left' => $minutesLeft,
            ],
        ];

        $this->sendToUser($user, $payload);
    }
}

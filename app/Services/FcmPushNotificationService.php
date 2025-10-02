<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushNotificationService
{
    private string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    private string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Send push notification to a specific device
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($this->serverKey)) {
            Log::warning('Firebase server key not configured');
            return false;
        }

        if (empty($fcmToken)) {
            Log::warning('FCM token is empty');
            return false;
        }

        $notification = [
            'to' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $notification);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'title' => $title,
                    'token' => substr($fcmToken, 0, 20) . '...'
                ]);
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('FCM push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send to multiple devices
     */
    public function sendToMultipleDevices(array $fcmTokens, string $title, string $body, array $data = []): array
    {
        $notification = [
            'registration_ids' => $fcmTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $notification);

            $result = $response->json();

            return [
                'success' => $response->successful(),
                'results' => $result['results'] ?? [],
                'success_count' => $result['success'] ?? 0,
                'failure_count' => $result['failure'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('FCM push notification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send new match notification
     */
    public function sendNewMatchNotification(string $fcmToken, string $matchName, int $matchId): bool
    {
        return $this->sendToDevice(
            $fcmToken,
            "It's a Match! 💕",
            "You and {$matchName} liked each other!",
            [
                'type' => 'new_match',
                'match_id' => (string) $matchId,
                'screen' => 'messages',
            ]
        );
    }

    /**
     * Send new message notification
     */
    public function sendNewMessageNotification(string $fcmToken, string $senderName, string $messagePreview, int $matchId): bool
    {
        return $this->sendToDevice(
            $fcmToken,
            "{$senderName} sent you a message",
            $messagePreview,
            [
                'type' => 'new_message',
                'match_id' => (string) $matchId,
                'screen' => 'messages',
            ]
        );
    }

    /**
     * Send new like notification
     */
    public function sendNewLikeNotification(string $fcmToken): bool
    {
        return $this->sendToDevice(
            $fcmToken,
            "Someone likes you! 😍",
            "You have a new like. Check who it is!",
            [
                'type' => 'new_like',
                'screen' => 'likes',
            ]
        );
    }
}

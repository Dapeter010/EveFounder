<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfoBipService
{
    protected $baseUrl;
    protected $otpBaseUrl;
    protected $apiKey;
    protected $applicationId;

    protected $messageId;

    public function __construct()
    {
        $this->baseUrl = config('sms.auth.infobip.base_url');
        $this->apiKey = config('sms.auth.infobip.api_key');
        $this->otpBaseUrl = config('sms.auth.infobip.otp_base_url');
        $this->applicationId = config('sms.auth.infobip.application_id');
        $this->messageId = config('sms.auth.infobip.message_id');
    }

    /**
     * Format phone to acceptable format by some SMS
     * Providers.
     * @param $value
     * @param false $isArray
     * @return array|string
     */
    private static function formatPhoneNumber($value, $isArray = false)
    {

        if ($isArray) {

            $numbers = [];

            foreach ($value as $item) {

                if (substr($item, 0, 1) == '+') {
                    $item = '44' . substr($item, 3);
                }

                if (substr($item, 0, 1) == '0') {
                    $item = '44' . substr($item, 1);
                }

                $numbers[] = $item;
            }

            return $numbers;

        } else {

            if (substr($value, 0, 1) == '+') {
                $value = '44' . substr($value, 3);
            }

            if (substr($value, 0, 1) == '0') {
                $value = '44' . substr($value, 1);
            }

            return $value;
        }
    }

    public function sendSms(string $to, string $message)
    {
        $url = $this->baseUrl . '/sms/2/text/advanced';

        $payload = [
            'messages' => [
                [
                    'destinations' => [
                        ['to' => $this::formatPhoneNumber($to)],
                    ],
                    'text' => $message,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $payload);
            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::info($e);
            throw new \Exception('SMS Sending Failed: ' . $e->getMessage());
        }
    }


    // Send OTP
    public function sendOtp(string $to)
    {
        $url = $this->otpBaseUrl . '/2fa/2/pin';
        Log::info($url);

        $payload = [
            'applicationId' => $this->applicationId,
            'messageId' => $this->messageId,
            'from' => (env('APP_ENV') == 'production' ? env('INFOBIP_SENDER') : '447491163443'),
            'to' => $this::formatPhoneNumber($to),
        ];

//        $response = Http::withHeaders([
//            'Authorization' => 'App ' . $this->apiKey,
//            'Content-Type' => 'application/json',
//            'Accept' => 'application/json',
//        ])->post($url, $payload);

//        if ($response->successful()) {
        if (true) {

//            {
//                "pinId": "9C817C6F8AF3D48F9FE553282AFA2B67",
//"to": "41793026727",
//"ncStatus": "NC_DESTINATION_REACHABLE",
//"smsStatus": "MESSAGE_SENT"
//}

//            return $response->json();

            return ["pinId" => "36346F0EB6E0C1F3D9DE83976FD3F98E",
                "to" => "41793026727",
                "ncStatus" => "NC_DESTINATION_REACHABLE",
                "smsStatus" => "MESSAGE_SENT"];

        }

//        throw new \Exception('Failed to send OTP: ' . $response->body());
    }

    // Verify OTP
    public function verifyOtp(string $pinId, string $otp)
    {

//        {
//            "pinId": "9C817C6F8AF3D48F9FE553282AFA2B67",
//"msisdn": "41793026727",
//"verified": true,
//"attemptsRemaining": 0
//}

        $url = $this->otpBaseUrl . '/2fa/2/pin/' . $pinId . '/verify';

        $payload = [
            'pin' => $otp,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'App ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);


        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to verify OTP: ' . $response->body());
    }

}

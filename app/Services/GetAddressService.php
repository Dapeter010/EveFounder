<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetAddressService
{
    protected $client;
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.getaddress.id_url');
        $this->apiKey = config('services.getaddress.api_key');
    }

    public function autocomplete($term)
    {
        try {
            $response = Http::withHeaders([
                    'api-key' => $this->apiKey,
                ]
            )->get($this->baseUrl . "/{$term}?api-key=" . $this->apiKey);

            if ($response->successful()) {
                $data = $response->json();

                $formattedResponse = [];
                if (isset($data['suggestions']) && is_array($data['suggestions'])) {
                    foreach ($data['suggestions'] as $result) {
                        $a = explode(',', $result['address']);
                        $formattedResponse[] = [
                            'address' => $result['address'] ?? null,
                            'building_name' => trim($a[0]) ?? null,
                            'building_number' => trim($a[0]) ?? null,
                            'postcode' => trim($a[3]) ?? null,
                            'id' => $result['id'] ?? null,
                        ];
                    }
                }

                return array("address" => $formattedResponse);
            }
            Log::error('Error fetching address suggestions from getAddress.io: ' . $response->body());

            return null;

        } catch (\Exception $e) {
            Log::error('Error fetching address suggestions from getAddress.io: ' . $e->getMessage());
            return [
                'error' => 'An error occurred while fetching address suggestions.',
                'message' => $e->getMessage(),
            ];
        }
    }

}

<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeService
{
    /**
     * Create a new class instance.
     */

    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OS_API_KEY');
    }

    public function getAddressFromPostCode($postcode)
    {
        $url = env('OS_BASE_URL') . "find?query=$postcode&key=";
        try {
            $response = Http::withHeaders([
                'Key' => $this->apiKey,
            ])->get($url);
//            Log::info($response->json());
            if ($response->successful()) {
                $res = collect($response->json()['results'])->map(function ($result) {
                    $dpa = $result['DPA'] ?? [];
                    return [
                        'address' => $dpa['ADDRESS'] ?? "",
                        'building_name' => $dpa['THOROUGHFARE_NAME'] ?? "",
                        'building_number' => $dpa['BUILDING_NUMBER'] ?? "",
                        'postcode' => $dpa['POSTCODE'] ?? "",
                    ];
                });
                return array('address' => $res);
            }
            return null;
        } catch (ConnectionException $e) {
            Log::info($e);
            return null;
        }
    }
}

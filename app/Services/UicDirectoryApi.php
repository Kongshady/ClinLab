<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UicDirectoryApi
{
    /**
     * Fetch the full unified student/employee list from the UIC API.
     *
     * @return array  Decoded JSON (the items array)
     * @throws \RuntimeException on auth or network failure
     */
    public function fetchUnifiedList(): array
    {
        $config = config('services.uic_api');
        $url = rtrim($config['base'], '/') . $config['unified_list'];

        Log::info('[UIC API] Fetching unified list from: ' . $config['unified_list']);

        $response = Http::withHeaders([
                'X-API-Client-ID' => $config['client_id'],
                'X-API-Client-Secret' => $config['client_secret'],
                'Accept' => 'application/json',
            ])
            ->timeout($config['timeout'])
            ->retry(2, 5000)          // retry once after 5 s
            ->get($url);

        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('[UIC API] Authentication failed (HTTP ' . $response->status() . ')');
            throw new \RuntimeException('UIC API authentication failed. Check your Client-ID / Client-Secret.');
        }

        if ($response->failed()) {
            Log::error('[UIC API] Request failed (HTTP ' . $response->status() . ')');
            throw new \RuntimeException('UIC API request failed with HTTP ' . $response->status());
        }

        $body = $response->json();

        // The API may return { data: [...] } or just [...]
        if (isset($body['data']) && is_array($body['data'])) {
            return $body['data'];
        }

        if (is_array($body)) {
            return $body;
        }

        Log::warning('[UIC API] Unexpected response shape.');
        return [];
    }
}

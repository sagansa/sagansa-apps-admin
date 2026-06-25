<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Memicu permintaan lokasi on-demand ke device pegawai lewat services/api.
 *
 * apps/admin membaca DB langsung untuk hal lain, tetapi trigger FCM harus lewat
 * services/api (yang punya FCM SDK). Admin mengautentikasi memakai token Sanctum
 * (env TRACKING_API_TOKEN) karena berbagi DB auth (mysql_auth) dengan services/api.
 */
class EmployeeTrackingService
{
    /**
     * Picu permintaan lokasi on-demand ke pegawai.
     *
     * @param  int  $userId  Id user pegawai.
     * @return array{success: bool, message: string}
     */
    public function requestLocation(int $userId): array
    {
        $token = config('services.tracking.api_token');
        $baseUrl = rtrim((string) config('services.api.url'), '/');

        if (empty($token) || empty($baseUrl)) {
            Log::warning('EmployeeTrackingService: TRACKING_API_TOKEN atau API_URL belum dikonfigurasi.');
            return [
                'success' => false,
                'message' => 'Konfigurasi tracking belum lengkap (TRACKING_API_TOKEN / API_URL). Hubungi administrator sistem.',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->acceptJson()
                ->post("{$baseUrl}/admin/track-location/{$userId}");

            if ($response->successful()) {
                $body = $response->json();
                $status = $body['data']['status'] ?? 'pending';

                return [
                    'success' => true,
                    'message' => 'Permintaan lokasi terkirim. Status: ' . $status . '. Lokasi akan muncul di peta setelah perangkat merespons.',
                ];
            }

            $message = $response->json('message')
                ?? 'Gagal memicu pelacakan (HTTP ' . $response->status() . ').';

            return [
                'success' => false,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::warning('EmployeeTrackingService: exception saat request lokasi.', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Tidak dapat menghubungi layanan pelacakan. Coba beberapa saat lagi.',
            ];
        }
    }
}

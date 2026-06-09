<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class CloudinaryService
{
    private static function sslVerifyOption(): bool
    {
        return filter_var(config('services.cloudinary.verify', true), FILTER_VALIDATE_BOOL);
    }

    public static function uploadProductImage(UploadedFile $file): array
    {
        $folder = (string) config('services.cloudinary.folder', 'products');

        return self::upload($file, $folder);
    }

    public static function uploadLogoImage(UploadedFile $file): array
    {
        return self::upload($file, 'logos');
    }

    private static function upload(UploadedFile $file, string $folder): array
    {
        $cloudName = (string) config('services.cloudinary.cloud_name');
        $apiKey = (string) config('services.cloudinary.api_key');
        $apiSecret = (string) config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new \RuntimeException('Cloudinary credentials are not configured.');
        }

        $timestamp = time();
        $signature = sha1("folder={$folder}&timestamp={$timestamp}{$apiSecret}");

        $response = Http::withOptions(['verify' => self::sslVerifyOption()])
            ->asMultipart()
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            [
                'name' => 'file',
                'contents' => fopen($file->getRealPath(), 'r'),
                'filename' => $file->getClientOriginalName(),
            ],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => (string) $timestamp],
            ['name' => 'folder', 'contents' => $folder],
            ['name' => 'signature', 'contents' => $signature],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Cloudinary upload failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'url' => $data['secure_url'] ?? null,
            'public_id' => $data['public_id'] ?? null,
        ];
    }

    public static function destroyImage(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        $cloudName = (string) config('services.cloudinary.cloud_name');
        $apiKey = (string) config('services.cloudinary.api_key');
        $apiSecret = (string) config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return;
        }

        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

        Http::withOptions(['verify' => self::sslVerifyOption()])
            ->asForm()
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            ]);
    }
}

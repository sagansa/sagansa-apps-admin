<?php

namespace App\Filament\Forms;

use App\Rules\ImageFile;
use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;

class ImageInput extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules([new ImageFile])
            ->nullable()
            ->openable()
            ->downloadable()
            ->fetchFileInformation(false)
            ->disk('img_service')
            ->imagePreviewHeight('120px')
            ->saveUploadedFileUsing(function (UploadedFile $file): ?string {
                $processedPath = app(\App\Services\ImageProcessor::class)->process($file);
                $mime = mime_content_type($processedPath) ?: 'application/octet-stream';
                $processedExt = pathinfo($processedPath, PATHINFO_EXTENSION);

                $newFile = new UploadedFile(
                    $processedPath,
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.' . $processedExt,
                    $mime,
                    null,
                    true,
                );

                $token = config('services.image.api_token');
                $serviceUrl = config('services.image.service_url', 'https://img.sagansa.id');

                \Illuminate\Support\Facades\Log::debug('ImageInput: upload config', [
                    'token_length' => strlen($token ?? ''),
                    'token_first10' => substr($token ?? '', 0, 10),
                    'service_url' => $serviceUrl,
                ]);

                if ($token) {
                    try {
                        $directory = trim($this->getDirectory() ?? '', '/');
                        $uploadUrl = rtrim($serviceUrl, '/') . '/api/upload';

                        $request = \Illuminate\Support\Facades\Http::withToken($token)
                            ->timeout(30)
                            ->acceptJson();

                        $fileContents = file_get_contents($processedPath);
                        $filename = $newFile->getClientOriginalName();

                        \Illuminate\Support\Facades\Log::debug('ImageInput: sending request', [
                            'url' => $uploadUrl,
                            'filename' => $filename,
                            'directory' => $directory,
                            'file_size' => strlen($fileContents),
                        ]);

                        if ($directory) {
                            $response = $request->attach('image', $fileContents, $filename)
                                ->post($uploadUrl, [
                                    'directory' => $directory,
                                ]);
                        } else {
                            $response = $request->attach('image', $fileContents, $filename)
                                ->post($uploadUrl);
                        }

                        if ($response->successful()) {
                            $data = $response->json();
                            \Illuminate\Support\Facades\Log::debug('ImageInput: upload response', [
                                'data' => $data,
                            ]);
                            @unlink($processedPath);
                            if (isset($data['path'])) {
                                return $data['path'];
                            }
                        }

                        \Illuminate\Support\Facades\Log::error('ImageInput: Upload to img service failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                            'headers' => $response->headers(),
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('ImageInput: Exception during upload to img service', [
                            'message' => $e->getMessage(),
                        ]);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('ImageInput: IMAGE_SERVICE_TOKEN not configured. Uploading to local storage.');
                }

                // Fallback to local storage
                $path = $newFile->store($this->getDirectory(), $this->getDiskName());
                @unlink($processedPath);

                return $path;
            })
            ->deleteUploadedFileUsing(function ($file) {
                $token = config('services.image.api_token');
                $serviceUrl = config('services.image.service_url', 'https://img.sagansa.id');

                \Illuminate\Support\Facades\Log::debug('ImageInput: delete config', [
                    'token' => $token ? substr($token, 0, 6) . '...' : null,
                    'service_url' => $serviceUrl,
                ]);

                if (!$token) {
                    $this->getDisk()->delete($file);
                    return;
                }

                try {
                    $deleteUrl = rtrim($serviceUrl, '/') . '/api/images';
                    $response = \Illuminate\Support\Facades\Http::withToken($token)
                        ->timeout(15)
                        ->acceptJson()
                        ->delete($deleteUrl, [
                            'path' => $file,
                        ]);

                    if ($response->successful()) {
                        return;
                    }

                    \Illuminate\Support\Facades\Log::error('ImageInput: Delete from img service failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'path' => $file,
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('ImageInput: Exception during delete from img service', [
                        'message' => $e->getMessage(),
                        'path' => $file,
                    ]);
                }

                // Fallback: also try to delete locally just in case
                $this->getDisk()->delete($file);
            });

        $this->acceptedFileTypes(['image/*', 'image/heic', 'image/heif']);
    }

    public function image(): static
    {
        return $this;
    }

    public function acceptedFileTypes(array | \Illuminate\Contracts\Support\Arrayable | Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }
}

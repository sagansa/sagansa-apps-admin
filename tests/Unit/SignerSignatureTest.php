<?php

namespace Tests\Unit;

use App\Filament\Resources\Panel\SignerResource;
use App\Models\Signer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Mutasi aset tanda tangan resource "Penandatangan Surat" — tanpa database.
 *
 * Unggahan kini disimpan di img service (img.sagansa.id) sebagai path relatif
 * di `signature_asset_path`; `signature_asset` (base64 legacy) hanya dikosongkan
 * saat aset diganti/dihapus. Pratinjau menerima URL img service atau data URI
 * base64 legacy.
 */
class SignerSignatureTest extends TestCase
{
    /** PNG 1×1 px valid. */
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private const NEW_PATH = 'images/Signer/baru.webp';

    private const OLD_PATH = 'images/Signer/lama.webp';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.image.api_token' => 'token-tes',
            'services.image.service_url' => 'https://img.sagansa.id',
        ]);
    }

    public function test_upload_is_mutated_into_image_service_path(): void
    {
        $data = SignerResource::mutateSignatureData([
            'user_id' => 7,
            'role' => 'direktur',
            'signatureUpload' => self::NEW_PATH,
            'removeSignature' => false,
        ]);

        $this->assertSame(self::NEW_PATH, $data['signature_asset_path']);
        $this->assertNull($data['signature_asset']);
        $this->assertArrayNotHasKey('signatureUpload', $data);
        $this->assertArrayNotHasKey('removeSignature', $data);
    }

    public function test_array_upload_payload_is_supported(): void
    {
        $data = SignerResource::mutateSignatureData([
            'signatureUpload' => [self::NEW_PATH],
        ]);

        $this->assertSame(self::NEW_PATH, $data['signature_asset_path']);
        $this->assertNull($data['signature_asset']);
    }

    public function test_remove_checkbox_clears_both_columns(): void
    {
        $data = SignerResource::mutateSignatureData([
            'signature_asset' => 'lama',
            'signature_asset_path' => self::OLD_PATH,
            'removeSignature' => true,
        ]);

        $this->assertNull($data['signature_asset_path']);
        $this->assertNull($data['signature_asset']);
    }

    public function test_upload_wins_over_remove_checkbox(): void
    {
        $data = SignerResource::mutateSignatureData([
            'signatureUpload' => self::NEW_PATH,
            'removeSignature' => true,
        ]);

        $this->assertSame(self::NEW_PATH, $data['signature_asset_path']);
        $this->assertNull($data['signature_asset']);
    }

    public function test_no_upload_and_no_remove_leaves_columns_untouched(): void
    {
        $data = SignerResource::mutateSignatureData([
            'user_id' => 7,
            'role' => 'direktur',
            'display_name' => 'Budi',
        ]);

        // Kolom aset tidak boleh ikut di-update — mengubah nama/jabatan
        // tidak menghapus aset tersimpan.
        $this->assertArrayNotHasKey('signature_asset_path', $data);
        $this->assertArrayNotHasKey('signature_asset', $data);
        $this->assertSame('Budi', $data['display_name']);
    }

    public function test_previous_image_is_deleted_when_replaced(): void
    {
        Http::fake();

        $data = SignerResource::mutateSignatureData(
            ['signatureUpload' => self::NEW_PATH],
            self::OLD_PATH,
        );

        $this->assertSame(self::NEW_PATH, $data['signature_asset_path']);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/api/images')
                && str_contains($request->body(), 'lama.webp');
        });
    }

    public function test_previous_image_is_deleted_when_removed(): void
    {
        Http::fake();

        $data = SignerResource::mutateSignatureData(
            ['removeSignature' => true],
            self::OLD_PATH,
        );

        $this->assertNull($data['signature_asset_path']);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/api/images')
                && str_contains($request->body(), 'lama.webp');
        });
    }

    public function test_delete_is_skipped_without_token(): void
    {
        config(['services.image.api_token' => null]);
        Http::fake();

        SignerResource::mutateSignatureData(
            ['removeSignature' => true],
            self::OLD_PATH,
        );

        Http::assertNothingSent();
    }

    public function test_signature_url_prefers_image_service_path(): void
    {
        $withPath = (new Signer)->forceFill([
            'signature_asset_path' => self::NEW_PATH,
            'signature_asset' => self::PNG_BASE64,
        ]);

        $this->assertSame(
            'https://img.sagansa.id/storage/'.self::NEW_PATH,
            $withPath->signatureUrl(),
        );

        $legacy = (new Signer)->forceFill([
            'signature_asset_path' => null,
            'signature_asset' => self::PNG_BASE64,
        ]);

        $this->assertSame('data:image/png;base64,'.self::PNG_BASE64, $legacy->signatureUrl());

        $empty = (new Signer)->forceFill([
            'signature_asset_path' => null,
            'signature_asset' => null,
        ]);

        $this->assertNull($empty->signatureUrl());
    }

    public function test_preview_returns_data_uri_only_for_valid_png(): void
    {
        $valid = (new Signer)->forceFill(['signature_asset' => self::PNG_BASE64]);
        $this->assertSame(
            'data:image/png;base64,'.self::PNG_BASE64,
            $valid->signatureDataUri(),
        );

        $corrupt = (new Signer)->forceFill([
            'signature_asset' => base64_encode('bukan-png'),
        ]);
        $this->assertNull($corrupt->signatureDataUri());

        $empty = (new Signer)->forceFill(['signature_asset' => null]);
        $this->assertNull($empty->signatureDataUri());
    }
}

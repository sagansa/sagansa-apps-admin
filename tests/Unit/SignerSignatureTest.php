<?php

namespace Tests\Unit;

use App\Filament\Resources\Panel\SignerResource;
use App\Models\Signer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mutasi aset tanda tangan resource "Penandatangan Surat" (fase 1c) —
 * tanpa database: berkas unggahan sementara dibaca → base64 di
 * signature_asset, dan pratinjau hanya menerima PNG sah.
 */
class SignerSignatureTest extends TestCase
{
    /** PNG 1×1 px valid. */
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_upload_is_mutated_into_base64_asset_and_temp_file_removed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('tmp/ttd.png', base64_decode(self::PNG_BASE64));

        $data = SignerResource::mutateSignatureData([
            'user_id' => 7,
            'role' => 'direktur',
            'signatureUpload' => 'tmp/ttd.png',
            'removeSignature' => false,
        ]);

        $this->assertSame(self::PNG_BASE64, $data['signature_asset']);
        $this->assertArrayNotHasKey('signatureUpload', $data);
        $this->assertArrayNotHasKey('removeSignature', $data);
        Storage::disk('local')->assertMissing('tmp/ttd.png');
    }

    public function test_array_upload_payload_is_supported(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('tmp/ttd.png', base64_decode(self::PNG_BASE64));

        $data = SignerResource::mutateSignatureData([
            'signatureUpload' => ['tmp/ttd.png'],
        ]);

        $this->assertSame(self::PNG_BASE64, $data['signature_asset']);
    }

    public function test_remove_checkbox_clears_asset_without_upload(): void
    {
        $data = SignerResource::mutateSignatureData([
            'signature_asset' => 'lama',
            'removeSignature' => true,
        ]);

        $this->assertNull($data['signature_asset']);
    }

    public function test_upload_wins_over_remove_checkbox(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('tmp/ttd.png', base64_decode(self::PNG_BASE64));

        $data = SignerResource::mutateSignatureData([
            'signatureUpload' => 'tmp/ttd.png',
            'removeSignature' => true,
        ]);

        $this->assertSame(self::PNG_BASE64, $data['signature_asset']);
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

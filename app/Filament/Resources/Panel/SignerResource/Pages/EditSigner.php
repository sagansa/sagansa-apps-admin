<?php

namespace App\Filament\Resources\Panel\SignerResource\Pages;

use App\Filament\Resources\Panel\SignerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSigner extends EditRecord
{
    protected static string $resource = SignerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * `signature_asset` (base64 PNG, ≈1,4 MB untuk PNG 1 MB) TIDAK boleh
     * ikut ke snapshot Livewire: Filament default mengirim seluruh atribut
     * record ke browser lalu dikirim balik di SETIAP POST update. Body
     * melebihi `post_max_size` → PHP membuang body → `_token` hilang →
     * respons 419.
     *
     * Model sudah menandainya `$hidden`; pengulangan di sini disengaja
     * sebagai pengaman kedua di jalur yang persis menyebabkan 419.
     * Aset kini dibaca lewat `signature_asset_path` (path img service,
     * kecil); pratinjau diambil dari record, bukan dari state form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['signature_asset']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return SignerResource::mutateSignatureData(
            $data,
            $this->getRecord()->signature_asset_path,
        );
    }
}

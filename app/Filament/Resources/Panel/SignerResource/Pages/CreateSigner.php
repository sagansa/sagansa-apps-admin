<?php

namespace App\Filament\Resources\Panel\SignerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\SignerResource;

class CreateSigner extends CreateRecord
{
    protected static string $resource = SignerResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SignerResource::mutateSignatureData($data);
    }
}

<?php

namespace App\Filament\Resources\Panel\ClosingStoreResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ClosingStoreResource;

class EditClosingStore extends EditRecord
{
    protected static string $resource = ClosingStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * Setelah closing store diedit, pastikan seluruh record yang ter-attach
     * (daily salary, invoice purchase, fuel service) berstatus paid (2).
     *
     * Menggantikan event model `self::updated` lama yang bergantung pada
     * properti `is_attached` (yang tidak pernah didefinisikan) sehingga
     * selalu mereset status ke unpaid (1) setiap kali closing store diedit.
     *
     * Catatan: Detach via RelationManager menangani reset status ke 1 secara
     * mandiri, jadi tidak konflik dengan handler ini.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $record->dailySalaries()->update(['status' => 2]);
        $record->invoicePurchases()->update(['payment_status' => 2]);
        $record->fuelServices()->update(['status' => 2]);
    }
}

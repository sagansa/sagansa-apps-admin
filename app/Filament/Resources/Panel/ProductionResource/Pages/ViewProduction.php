<?php

namespace App\Filament\Resources\Panel\ProductionResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\ProductionResource;
use App\Services\ProductionLedgerService;

class ViewProduction extends ViewRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action Apply/Revert mutasi stok. Idempoten via applied_at.
            Actions\Action::make('toggleApplyStock')
                ->label(fn () => $this->record->applied_at ? 'Batalkan Stok' : 'Terapkan Stok')
                ->icon(fn () => $this->record->applied_at ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->applied_at ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->applied_at
                    ? 'Batalkan mutasi stok?'
                    : 'Terapkan mutasi stok?')
                ->modalDescription(fn () => $this->record->applied_at
                    ? 'Stok ingredient akan dikembalikan, stok output akan dikurangi. Aman bila ada kesalahan input.'
                    : 'Stok ingredient akan dikurangi, stok output akan ditambah. Pastikan item produksi sudah benar.')
                ->action(function () {
                    $svc = app(ProductionLedgerService::class);
                    if ($this->record->applied_at) {
                        $ok = $svc->revert($this->record);
                        $msg = $ok ? 'Mutasi stok dibatalkan.' : 'Gagal membatalkan mutasi stok.';
                    } else {
                        $ok = $svc->apply($this->record);
                        $msg = $ok ? 'Mutasi stok diterapkan.' : 'Gagal menerapkan mutasi stok.';
                    }
                    Notification::make()
                        ->title($msg)
                        ->{$ok ? 'success' : 'danger'}()
                        ->send();
                    $this->refreshFormData(['applied_at']);
                })
                // Disable bila belum punya items sama sekali.
                ->hidden(fn () => $this->record->items()->count() === 0),

            Actions\EditAction::make(),
        ];
    }
}

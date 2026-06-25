<?php

namespace App\Filament\Resources\Panel\EmployeeResource\Pages;

use App\Services\EmployeeTrackingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\EmployeeResource;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Picu permintaan lokasi on-demand ke perangkat pegawai via FCM.
            Actions\Action::make('trackLocation')
                ->label('Lacak Lokasi')
                ->icon('heroicon-o-map-pin')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Minta lokasi pegawai sekarang?')
                ->modalDescription('Permintaan akan dikirim ke perangkat pegawai. Lokasi terbaru akan muncul di halaman Lacak Lokasi Pegawai setelah perangkat merespons.')
                ->modalSubmitActionLabel('Kirim permintaan')
                ->action(function () {
                    $userId = $this->getRecord()->user_id;

                    if (! $userId) {
                        Notification::make()
                            ->title('Tidak dapat melacak')
                            ->body('Pegawai ini tidak terhubung ke akun user.')
                            ->danger()
                            ->send();
                        return;
                    }

                    /** @var EmployeeTrackingService $service */
                    $service = app(EmployeeTrackingService::class);
                    $result = $service->requestLocation((int) $userId);

                    Notification::make()
                        ->title($result['success'] ? 'Permintaan terkirim' : 'Gagal')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}

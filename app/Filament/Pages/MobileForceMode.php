<?php

namespace App\Filament\Pages;

use App\Models\ServiceSetting;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

class MobileForceMode extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \UnitEnum | null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Mode Mobile';

    protected static ?string $title = 'Mode Mobile';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'mode-mobile';

    protected string $view = 'filament.pages.mobile-force-mode';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data['forceMobileOnly'] = ServiceSetting::getValue('force_mobile_only', '1') === '1';
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Pengaturan Akses Panel')
                    ->description('Saat mode mobile aktif, hanya admin dan super-admin yang bisa mengakses panel ini. Nonaktifkan mode ini jika terjadi masalah pada aplikasi mobile.')
                    ->schema([
                        Toggle::make('forceMobileOnly')
                            ->label('Mode Mobile Only')
                            ->helperText('ON: Hanya admin & super-admin bisa akses panel. OFF: Semua pengguna bisa akses panel (darurat).')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                ServiceSetting::setValue('force_mobile_only', $state ? '1' : '0');
                                Notification::make()
                                    ->title($state ? 'Mode Mobile Only diaktifkan' : 'Mode darurat diaktifkan - semua pengguna dapat mengakses panel')
                                    ->success()
                                    ->send();
                            }),
                    ]),
            ])
            ->statePath('data');
    }
}

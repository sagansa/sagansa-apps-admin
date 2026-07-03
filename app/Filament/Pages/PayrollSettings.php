<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\HRD;
use App\Models\PayrollPeriodSetting;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \UnitEnum | null $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Payroll Settings';

    protected static ?string $title = 'Payroll Settings';

    protected static ?int $navigationSort = 40;

    protected static ?string $cluster = HRD::class;

    protected string $view = 'filament.pages.payroll-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $tenantId = $this->getTenantId();

        $setting = PayrollPeriodSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'start_day' => 26,
                'end_day' => 25,
                'transport_allowance_per_day' => 25000,
                'meal_allowance_per_day' => 20000,
                'late_penalty_per_hour' => 10000,
                'no_checkout_penalty' => 20000,
            ]
        );

        $this->form->fill($setting->toArray());
    }

    protected function getTenantId(): string
    {
        return Auth::user()->tenant_id
            ?? Store::first()?->tenant_id
            ?? DB::connection('mysql_auth')->table('tenants')->first()?->id
            ?? '00000000-0000-0000-0000-000000000000';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Siklus & Periode Penggajian')
                    ->description('Tentukan rentang tanggal cut-off penggajian bulanan.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('start_day')
                                ->label('Mulai Tanggal (Start Day)')
                                ->options(array_combine(range(1, 31), range(1, 31)))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, callable $set) => $set('end_day', $state == 1 ? 31 : ($state - 1))),

                            TextInput::make('end_day')
                                ->label('Selesai Tanggal (End Day)')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $tenantId = $this->getTenantId();
        $formData = $this->form->getState();

        PayrollPeriodSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            $formData
        );

        Notification::make()
            ->title('Pengaturan penggajian berhasil disimpan.')
            ->success()
            ->send();
    }
}

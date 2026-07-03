<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\MonthlySalary;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use App\Filament\Resources\Panel\MonthlySalaryResource\Pages;

use App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers\PresencesRelationManager;
use App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers\DailySalariesRelationManager;

class MonthlySalaryResource extends Resource
{
    protected static ?string $model = MonthlySalary::class;

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Salaries';

    protected static ?string $pluralLabel = 'Monthly Salaries';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return __('crud.monthlySalaries.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.monthlySalaries.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.monthlySalaries.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Karyawan & Periode')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Karyawan')
                        ->disabled(),

                    Select::make('status')
                        ->label('Status Slip Gaji')
                        ->options([
                            MonthlySalary::STATUS_DRAFT    => 'Draft',
                            MonthlySalary::STATUS_APPROVED => 'Approved',
                            MonthlySalary::STATUS_PAID     => 'Paid',
                        ])
                        ->required(),

                    DatePicker::make('period_start')
                        ->label('Tanggal Mulai')
                        ->disabled(),

                    DatePicker::make('period_end')
                        ->label('Tanggal Selesai')
                        ->disabled(),

                    TextInput::make('total_work_days')
                        ->label('Total Hari Kerja')
                        ->disabled()
                        ->numeric(),

                    TextInput::make('total_hours')
                        ->label('Total Jam Kerja')
                        ->disabled()
                        ->numeric(),
                ]),

            Section::make('Rincian Perhitungan Gaji')
                ->columns(2)
                ->schema([
                    TextInput::make('base_salary')
                        ->label('Gaji Utama Tenur (A)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric(),

                    TextInput::make('daily_salary_total')
                        ->label('Total Gaji Harian (B)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric(),

                    TextInput::make('total_salary')
                        ->label('Gaji Bersih Akhir (A - Potongan + B)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric()
                        ->columnSpanFull()
                        ->extraInputAttributes(['style' => 'font-weight: bold; color: #10b981; font-size: 1.1rem;']),
                ]),

            Section::make('Daftar Potongan / Denda')
                ->schema([
                    KeyValue::make('deductions')
                        ->label('Daftar Potongan Pengurang')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period_start')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),

                TextColumn::make('period_end')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_work_days')
                    ->label('Hari Kerja')
                    ->sortable(),

                TextColumn::make('total_hours')
                    ->label('Total Jam')
                    ->sortable(),

                TextColumn::make('base_salary')
                    ->label('Gaji Tenur')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('daily_salary_total')
                    ->label('Gaji Harian')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('total_salary')
                    ->label('Gaji Bersih')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Dibayarkan')
                    ->money('idr')
                    ->default('—')
                    ->sortable(),

                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->getStateUsing(fn (MonthlySalary $record): string =>
                        $record->paid_amount !== null
                            ? 'Rp ' . number_format(abs($record->selisih), 0, ',', '.')
                              . ($record->selisih > 0 ? ' (Kurang)' : ($record->selisih < 0 ? ' (Lebih)' : ''))
                            : '—'
                    )
                    ->color(fn (MonthlySalary $record): string =>
                        $record->paid_amount === null ? 'gray'
                            : ($record->selisih > 0 ? 'danger' : ($record->selisih < 0 ? 'warning' : 'success'))
                    )
                    ->badge(),

                TextColumn::make('payment_date')
                    ->label('Tgl Bayar')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray',
                        '2' => 'warning',
                        '3' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Draft',
                        '2' => 'Approved',
                        '3' => 'Paid',
                        default => $state,
                    }),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),

                    // ── Approve ──────────────────────────────────────────────
                    \Filament\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Slip Gaji')
                        ->modalDescription('Status slip gaji akan diubah menjadi Approved.')
                        ->visible(fn (MonthlySalary $record) => $record->status === MonthlySalary::STATUS_DRAFT)
                        ->action(function (MonthlySalary $record) {
                            $record->update(['status' => MonthlySalary::STATUS_APPROVED]);
                            Notification::make()
                                ->title('Slip gaji berhasil di-approve.')
                                ->success()
                                ->send();
                        }),

                    // ── Bayar Gaji ───────────────────────────────────────────
                    \Filament\Actions\Action::make('bayar')
                        ->label('Bayar Gaji')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn (MonthlySalary $record) => $record->status !== MonthlySalary::STATUS_PAID)
                        ->fillForm(fn (MonthlySalary $record) => [
                            'paid_amount'  => (float) $record->total_salary - (float) ($record->daily_salary_total ?? 0),
                            'payment_date' => now()->toDateString(),
                        ])
                        ->form(fn (MonthlySalary $record) => [
                            Placeholder::make('bank_account_info')
                                ->label('Rekening Bank Penerima')
                                ->content(function () use ($record) {
                                    $detail = $record->user?->applicantDetail;
                                    if ($detail) {
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='text-sm space-y-1 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700'>
                                                <div><strong>Bank:</strong> " . e($detail->bank_name ?? '—') . "</div>
                                                <div><strong>No. Rekening:</strong> " . e($detail->bank_account_number ?? '—') . "</div>
                                                <div><strong>Atas Nama:</strong> " . e($detail->bank_account_name ?? '—') . "</div>
                                                <div class='text-danger-600 font-medium'><strong>Biaya Admin Transfer:</strong> Rp " . number_format($detail->admin_fee ?? 0, 0, ',', '.') . "</div>
                                            </div>
                                        ");
                                    }
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-sm text-gray-500 italic bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            Data rekening bank tidak ditemukan.
                                        </div>
                                    ");
                                }),

                            Placeholder::make('salary_breakdown_info')
                                ->label('Rincian Gaji Bersih')
                                ->content(function () use ($record) {
                                    $monthlyPart = (float) $record->total_salary - (float) ($record->daily_salary_total ?? 0);
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-sm space-y-1 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            <div><strong>Gaji Bulanan Bersih (A - Potongan):</strong> Rp " . number_format($monthlyPart, 0, ',', '.') . "</div>
                                            <div><strong>Gaji Harian Total (B - Terbayar Terpisah):</strong> Rp " . number_format((float) ($record->daily_salary_total ?? 0), 0, ',', '.') . "</div>
                                            <hr class='border-gray-200 dark:border-gray-700 my-1' />
                                            <div class='text-primary-600 font-bold'><strong>Gaji Total Bulanan (A - Potongan + B):</strong> Rp " . number_format((float) $record->total_salary, 0, ',', '.') . "</div>
                                        </div>
                                    ");
                                }),

                            TextInput::make('paid_amount')
                                ->label('Nominal Dibayarkan (Rp)')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->live()
                                ->helperText(function ($state) use ($record): string {
                                    if ($state === null || $state === '') return '';
                                    $monthlyPart = (float) $record->total_salary - (float) ($record->daily_salary_total ?? 0);
                                    $selisih = $monthlyPart - (float) $state;
                                    if ($selisih > 0) return '⚠ Kurang bayar: Rp ' . number_format($selisih, 0, ',', '.');
                                    if ($selisih < 0) return '⚠ Lebih bayar: Rp ' . number_format(abs($selisih), 0, ',', '.');
                                    return '✓ Sesuai kalkulasi Gaji Bulanan Bersih.';
                                }),

                            DatePicker::make('payment_date')
                                ->label('Tanggal Pembayaran')
                                ->required()
                                ->default(now()),
                        ])
                        ->action(function (MonthlySalary $record, array $data) {
                            $record->update([
                                'paid_amount'  => $data['paid_amount'],
                                'payment_date' => $data['payment_date'],
                                'status'       => MonthlySalary::STATUS_PAID,
                            ]);
                            Notification::make()
                                ->title('Gaji berhasil dibayarkan. Status diubah ke Paid.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    // ── Bulk Approve ─────────────────────────────────────────
                    \Filament\Actions\BulkAction::make('bulk_approve')
                        ->label('Set Approved')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Slip Gaji Terpilih')
                        ->modalDescription('Status semua slip terpilih yang masih Draft akan diubah menjadi Approved.')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === MonthlySalary::STATUS_DRAFT) {
                                    $record->update(['status' => MonthlySalary::STATUS_APPROVED]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} slip gaji berhasil di-approve.")
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PresencesRelationManager::class,
            DailySalariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMonthlySalaries::route('/'),
            'create' => Pages\CreateMonthlySalary::route('/create'),
            'view'   => Pages\ViewMonthlySalary::route('/{record}'),
            'edit'   => Pages\EditMonthlySalary::route('/{record}/edit'),
        ];
    }
}

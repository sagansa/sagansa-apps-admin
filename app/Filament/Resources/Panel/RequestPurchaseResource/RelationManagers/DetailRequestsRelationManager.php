<?php

namespace App\Filament\Resources\Panel\RequestPurchaseResource\RelationManagers;

use App\Models\DetailRequest;
use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Auth;

class DetailRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'detailRequests';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->required()
                ->disabled(),
            Forms\Components\TextInput::make('quantity_plan')
                ->numeric()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product'),

                TextColumn::make('quantity_plan')
                    ->label('Qty Plan')
                    ->formatStateUsing(fn (DetailRequest $record) =>
                        number_format($record->quantity_plan, 0, ',', '.') . ' ' .
                            ($record->product?->unit?->unit ?? '')),

                TextColumn::make('paymentType.name')
                    ->label('Payment Type'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            '1' => 'warning',
                            '2' => 'success',
                            '3' => 'danger',
                            '4' => 'success',
                            '5' => 'danger',
                            '6' => 'gray',
                            default => $state,
                        }
                    )
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'process (waiting approval)',
                            '2' => 'done',
                            '3' => 'reject',
                            '4' => 'approved',
                            '5' => 'not valid',
                            '6' => 'not used',
                            default => $state,
                        }
                    ),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->hidden(fn ($record) => $record->status != '1')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Item')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui item request ini?')
                    ->action(function (DetailRequest $record) {
                        $record->update(['status' => '4']);
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->hidden(fn ($record) => $record->status != '1')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Item')
                    ->modalDescription('Apakah Anda yakin ingin menolak item request ini?')
                    ->action(function (DetailRequest $record) {
                        $record->update(['status' => '3']);
                    }),

                Action::make('markAsNotUsed')
                    ->label('Tidak Digunakan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->hidden(fn ($record) => in_array($record->status, ['2', '3', '5', '6']))
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Tidak Digunakan')
                    ->modalDescription('Apakah Anda yakin ingin menandai item request ini sebagai tidak digunakan?')
                    ->action(function (DetailRequest $record) {
                        $record->update(['status' => '6']);
                    }),

                EditAction::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([]);
    }
}

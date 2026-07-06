<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Columns\StatusColumn;
use App\Filament\Filters\SelectEmployeeFilter;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use App\Models\Readiness;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\ReadinessResource\Pages;
use App\Filament\Resources\Panel\ReadinessResource\RelationManagers;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class ReadinessResource extends Resource
{
    protected static ?string $model = Readiness::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;


    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return __('crud.readinesses.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.readinesses.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.readinesses.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageInput::make('image_selfie')
                        ->label('Selfie')

                        ->directory('images/Readiness'),

                    ImageInput::make('left_hand')
                        ->label('Left Hand')

                        ->directory('images/Readiness'),

                    ImageInput::make('right_hand')
                        ->label('Right Hand')

                        ->directory('images/Readiness'),

                    Select::make('status')
                        ->required(fn () => Auth::user()->hasRole('admin'))
                        ->hidden(fn ($operation) => $operation === 'create')
                        ->disabled(fn () => Auth::user()->hasRole('staff'))
                        ->preload()
                        ->options([
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'diperbaiki',
                            '4' => 'periksa ulang',
                        ]),

                    Notes::make('notes'),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $readinesses = Readiness::query();

        if (!Auth::user()->hasRole('admin') || !Auth::user()->hasRole('super-admin')) {
            $readinesses->where('created_by_id', Auth::id());
        }

        return $table
            ->poll('60s')
            ->query($readinesses)
            ->columns([
                ImageColumn::make('image_selfie')
                    ->disk(null)
                    ->visibility('public')
                    ->state(fn($record) => ImageHelper::getImageUrl($record->image_selfie))
                    ->url(fn($record) => ImageHelper::getImageUrl($record->image_selfie)),

                ImageColumn::make('left_hand')
                    ->disk(null)
                    ->visibility('public')
                    ->state(fn($record) => ImageHelper::getImageUrl($record->left_hand))
                    ->url(fn($record) => ImageHelper::getImageUrl($record->left_hand)),

                ImageColumn::make('right_hand')
                    ->disk(null)
                    ->visibility('public')
                    ->state(fn($record) => ImageHelper::getImageUrl($record->right_hand))
                    ->url(fn($record) => ImageHelper::getImageUrl($record->right_hand)),

                StatusColumn::make('status'),

                TextColumn::make('createdBy.name'),
            ])
            ->filters([
                SelectEmployeeFilter::make('created_by_id'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadinesses::route('/'),
            'create' => Pages\CreateReadiness::route('/create'),
            'view' => Pages\ViewReadiness::route('/{record}'),
            'edit' => Pages\EditReadiness::route('/{record}/edit'),
        ];
    }
}

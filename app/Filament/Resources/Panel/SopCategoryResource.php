<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\BaseTextInput;
use App\Models\SopCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Panel\SopCategoryResource\Pages;

class SopCategoryResource extends Resource
{
    protected static ?string $model = SopCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    public static function getModelLabel(): string
    {
        return 'Kategori SOP';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori SOP';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kategori SOP';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Kategori')->schema([
                Grid::make(['default' => 1])->schema([
                    BaseTextInput::make('name')
                        ->label('Nama Kategori')
                        ->unique(ignoreRecord: true),

                    TextInput::make('icon')
                        ->label('Ikon (opsional)')
                        ->nullable()
                        ->helperText('Nama ikon untuk kategori, dipetakan di aplikasi mobile.'),

                    TextInput::make('sort_order')
                        ->label('Urutan Tampil')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sops_count')
                    ->label('Jumlah SOP')
                    ->counts('sops')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                ActiveColumn::make('is_active'),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSopCategories::route('/'),
            'create' => Pages\CreateSopCategory::route('/create'),
            'edit' => Pages\EditSopCategory::route('/{record}/edit'),
        ];
    }
}

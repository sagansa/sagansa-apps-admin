<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseTextInput;
use App\Filament\Forms\ImageInput;
use App\Models\Sop;
use App\Models\SopCategory;
use App\Support\PublicStorageUrl;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Filament\Resources\Panel\SopResource\Pages;

class SopResource extends Resource
{
    protected static ?string $model = Sop::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    public static function getModelLabel(): string
    {
        return 'SOP';
    }

    public static function getPluralModelLabel(): string
    {
        return 'SOP (Standard Operation Procedure)';
    }

    public static function getNavigationLabel(): string
    {
        return 'SOP';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi SOP')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(['default' => 1])->schema([
                        BaseTextInput::make('title')
                            ->label('Judul'),

                        Select::make('sop_category_id')
                            ->label('Kategori')
                            ->options(fn () => SopCategory::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable()
                            ->helperText('Ringkasan tujuan SOP, tampil di daftar panduan mobile.'),

                        ImageInput::make('cover_image')
                            ->label('Gambar Cover (opsional)')
                            ->directory('images/Sop'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('Langkah-langkah')
                ->icon('heroicon-o-numbered-list')
                ->description('Urutan langkah SOP seperti yang dibaca staf di aplikasi mobile. Setiap langkah boleh diberi gambar panduan.')
                ->schema([
                    Repeater::make('steps')
                        ->label('')
                        ->relationship('steps')
                        ->schema([
                            TextInput::make('title')
                                ->label('Judul Langkah')
                                ->required(),

                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->rows(2)
                                ->nullable(),

                            ImageInput::make('image')
                                ->label('Gambar Panduan (opsional)')
                                ->directory('images/Sop')
                                ->imagePreviewHeight('160px'),
                        ])
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->addActionLabel('Tambah Langkah')
                        ->defaultItems(0)
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Publikasi')
                ->icon('heroicon-o-rocket-launch')
                ->schema([
                    Toggle::make('is_published')
                        ->label('Terbitkan (tampil di aplikasi mobile)')
                        ->default(false)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->getStateUsing(fn ($record) => PublicStorageUrl::from($record->cover_image))
                    ->circular(),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('steps_count')
                    ->label('Langkah')
                    ->counts('steps'),

                TextColumn::make('revision')
                    ->label('Revisi')
                    ->sortable(),

                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Terbit' : 'Draft')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sop_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                TernaryFilter::make('is_published')
                    ->label('Status')
                    ->trueLabel('Terbit')
                    ->falseLabel('Draft'),
            ])
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
            'index' => Pages\ListSops::route('/'),
            'create' => Pages\CreateSop::route('/create'),
            'edit' => Pages\EditSop::route('/{record}/edit'),
        ];
    }
}

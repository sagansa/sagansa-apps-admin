<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Store;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\BaseRepeaterSelect;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\StoreSelect;
use App\Models\Hygiene;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\HygieneResource\Pages;
use App\Models\Room;
use Filament\Forms\Components\Repeater;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;

class HygieneResource extends Resource
{
    protected static ?string $model = Hygiene::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;


    protected static ?string $cluster = Store::class;

    public static function getModelLabel(): string
    {
        return __('crud.hygienes.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.hygienes.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.hygienes.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    StoreSelect::make('store_id'),
                ]),
            ]),
            Section::make()->schema(
                self::getItemsRepeater(),
            ),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user() && Auth::user()->hasRole('staff')) {
            $query->where('created_by_id', Auth::id());
        }
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns(self::getColumns())
            ->filters([
                SelectStoreFilter::make('store_id'),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->mutateFormDataUsing(function (array $data): array {
                            if (Auth::user() && Auth::user()->hasRole('admin')) {
                                $data['approved_by_id'] = Auth::id();
                            }
                            return $data;
                        }),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function getColumns(): array
    {
        return [
            TextColumn::make('store.nickname'),
            TextColumn::make('created_at'),
            TextColumn::make('createdBy.name')
                ->visible(fn() => auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin'))),
            TextColumn::make('approvedBy.name'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\HygieneOfRoomsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHygienes::route('/'),
            // 'create' => Pages\CreateHygiene::route('/create'),
            // 'view' => Pages\ViewHygiene::route('/{record}'),
            // 'edit' => Pages\EditHygiene::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): array
    {
        return [
            Repeater::make('hygieneOfRooms')
                ->hiddenLabel()
                ->default(fn () => Room::orderBy('name', 'asc')->get()->map(fn($item) => [
                    'room_id' => $item->id,
                ])->toArray())
                ->relationship()
                ->addable(false)
                ->deletable(false)
                ->schema([
                    BaseRepeaterSelect::make('room_id')
                        ->relationship('room', 'name'),
                    ImageInput::make('image')
                        ->multiple()
                        ->hiddenLabel()
                        ->directory('images/Hygiene'),
                ])
        ];
    }
}

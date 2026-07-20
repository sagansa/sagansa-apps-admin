<?php

namespace App\Filament\Resources\Panel\RecipeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\RecipeResource;
use App\Models\Recipe;

class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * Setelah update: kalau resep ini diaktifkan, nonaktifkan resep lain untuk
     * produk output yang sama (constraint 1 resep aktif per produk).
     */
    protected function afterSave(): void
    {
        if (!empty($this->record->is_active)) {
            Recipe::where('product_id', $this->record->product_id)
                ->where('id', '<>', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}

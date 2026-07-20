<?php

namespace App\Filament\Resources\Panel\RecipeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\RecipeResource;
use Illuminate\Support\Facades\Auth;

class CreateRecipe extends CreateRecord
{
    protected static string $resource = RecipeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }

    /**
     * Setelah simpan resep baru: kalau ditandai aktif, nonaktifkan resep lain
     * untuk produk output yang sama (constraint 1 resep aktif per produk).
     */
    protected function afterCreate(): void
    {
        if (!empty($this->record->is_active)) {
            \App\Models\Recipe::where('product_id', $this->record->product_id)
                ->where('id', '<>', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}

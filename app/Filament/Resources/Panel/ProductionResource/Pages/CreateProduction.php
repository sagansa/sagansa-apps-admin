<?php

namespace App\Filament\Resources\Panel\ProductionResource\Pages;

use App\Models\ProductionItem;
use App\Models\Recipe;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\ProductionResource;
use Illuminate\Support\Facades\Auth;

class CreateProduction extends CreateRecord
{
    protected static string $resource = ProductionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();
        $data['status'] = $data['status'] ?? '1';

        return $data;
    }

    /**
     * Setelah record production dibuat: bila recipe_id diisi, auto-prefill
     * production_items dari ingredient resep (snapshot) + 1 baris output dari
     * produk resep. User bisa edit qty di tab Item Produksi setelah ini.
     */
    protected function afterCreate(): void
    {
        $recipeId = $this->record->recipe_id;
        if (!$recipeId) {
            return;
        }

        $recipe = Recipe::with(['ingredients', 'product', 'outputUnit'])->find($recipeId);
        if (!$recipe) {
            return;
        }

        // 1) Baris OUTPUT: produk resep, qty = output_qty resep.
        ProductionItem::create([
            'production_id' => $this->record->id,
            'product_id'    => $recipe->product_id,
            'direction'     => 'out',
            'source'        => 'recipe_default',
            'quantity'      => $recipe->output_qty,
            'unit_id'       => $recipe->output_unit_id ?? $recipe->product?->unit_id,
            'recipe_ingredient_id' => null,
        ]);

        // 2) Baris INPUT (bahan baku) tiap ingredient resep.
        foreach ($recipe->ingredients as $ing) {
            ProductionItem::create([
                'production_id' => $this->record->id,
                'product_id'    => $ing->product_id,
                'direction'     => 'in',
                'source'        => 'recipe_default',
                'quantity'      => $ing->quantity,
                'unit_id'       => $ing->unit_id ?? $ing->product?->unit_id,
                'recipe_ingredient_id' => $ing->id,
                'is_optional'   => $ing->is_optional,
            ]);
        }

        Notification::make()
            ->title('Item produksi terisi otomatis dari resep')
            ->body('Periksa & sesuaikan qty di tab "Item Produksi".')
            ->success()
            ->send();
    }
}

<?php

namespace App\Filament\Resources\Panel\ProductOnlineGroupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\ProductOnlineGroupResource;
use Illuminate\Support\Facades\Auth;

class CreateProductOnlineGroup extends CreateRecord
{
    protected static string $resource = ProductOnlineGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        unset($data['selected_product_ids'], $data['selected_image_ids'], $data['filter_category_id']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getRawState();

        $record->products()->sync($data['selected_product_ids'] ?? []);

        $selectedImageIds = json_decode($data['selected_image_ids'] ?? '[]', true) ?? [];
        $record->images()->delete();
        foreach ($selectedImageIds as $order => $imageId) {
            $record->images()->create([
                'product_image_id' => $imageId,
                'order' => $order,
            ]);
        }

        $first = $record->images()->with('image')->orderBy('order')->first();
        $record->updateQuietly(['image' => $first?->image?->getImageUrl()]);
    }
}

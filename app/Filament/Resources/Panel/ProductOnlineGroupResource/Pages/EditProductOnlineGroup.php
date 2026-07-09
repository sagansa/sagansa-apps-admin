<?php

namespace App\Filament\Resources\Panel\ProductOnlineGroupResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ProductOnlineGroupResource;

class EditProductOnlineGroup extends EditRecord
{
    protected static string $resource = ProductOnlineGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['selected_product_ids'] = $this->record->products->pluck('id')->toArray();
        $data['selected_image_ids'] = json_encode($this->record->images->pluck('product_image_id')->toArray());
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['selected_product_ids'], $data['selected_image_ids'], $data['filter_category_id']);
        return $data;
    }

    protected function afterSave(): void
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

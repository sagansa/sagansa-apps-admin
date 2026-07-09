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
        return $data;
    }
}

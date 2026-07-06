<?php

namespace App\Filament\Resources\Panel\ClosingStoreResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\ClosingStoreResource;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\ClosingStore;
use App\Models\AccountCashless;
use App\Models\ShiftStore;
use Filament\Notifications\Notification;

class ListClosingStores extends ListRecords
{
    protected static string $resource = ClosingStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label(__('filament-actions::create.single.label', ['label' => static::$resource::getModelLabel()]))
                ->action(function () {
                    $user = auth()->user();
                    
                    if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                        return redirect(static::$resource::getUrl('create'));
                    }

                    $today = now()->toDateString();
                    
                    // Get the user's first store (or active store)
                    $store = $user->stores()->first();
                    
                    // Get the first active shift (or default)
                    $shift = ShiftStore::first();

                    if (!$store) {
                        return redirect(static::$resource::getUrl('create'));
                    }

                    if (!$shift) {
                        return redirect(static::$resource::getUrl('create'));
                    }

                    // Check if already exists for this store, shift, and date
                    $existing = ClosingStore::where('store_id', $store->id)
                        ->where('shift_store_id', $shift->id)
                        ->where('date', $today)
                        ->first();

                    if ($existing) {
                        Notification::make()
                            ->title('Mengarahkan ke laporan Closing')
                            ->body('Laporan shift untuk hari ini sudah ada.')
                            ->info()
                            ->send();
                        
                        return redirect(static::$resource::getUrl('edit', ['record' => $existing]));
                    }

                    // Create a new draft closing store
                    $newClosing = ClosingStore::create([
                        'store_id' => $store->id,
                        'shift_store_id' => $shift->id,
                        'date' => $today,
                        'cash_from_yesterday' => static::$resource::getCashForTomorrow($store->id),
                        'cash_for_tomorrow' => 0,
                        'total_cash_transfer' => 0,
                        'status' => 1, // belum diperiksa
                        'created_by_id' => $user->id,
                    ]);

                    // Auto populate cashless accounts
                    $accounts = AccountCashless::where('store_id', $store->id)->get();
                    foreach ($accounts as $account) {
                        $newClosing->cashlesses()->create([
                            'account_cashless_id' => $account->id,
                            'bruto_apl' => 0,
                        ]);
                    }

                    Notification::make()
                        ->title('Draf laporan Closing dibuat')
                        ->success()
                        ->send();

                    return redirect(static::$resource::getUrl('edit', ['record' => $newClosing]));
                })
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'belum diperiksa' => Tab::make()->query(fn ($query) => $query->where('status', '1')),
            'valid' => Tab::make()->query(fn ($query) => $query->where('status', '2')),
            'perbaiki' => Tab::make()->query(fn ($query) => $query->where('status', '3')),
            'periksa ulang' => Tab::make()->query(fn ($query) => $query->where('status', '4')),
        ];
    }
}

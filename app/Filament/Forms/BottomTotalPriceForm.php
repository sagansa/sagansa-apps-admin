<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\{RichEditor, TextInput};
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class BottomTotalPriceForm
{
    public static function schema(): array
    {
        return [

            CurrencyInput::make('shipping_cost')
                ->label('Shipping Cost')
                ->live(debounce: 500)
                ->afterStateUpdated(function (Get $get, Set $set) {
                    SalesProductForm::updateTotalPriceFromRoot($get, $set);
                }),

            CurrencyInput::make('total_price')
                ->label('Total Price')
                ->readOnly(),

            Notes::make('notes'),

        ];
    }
}

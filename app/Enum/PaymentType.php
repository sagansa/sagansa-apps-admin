<?php

namespace App\Enum;

/**
 * Nilai kolom `payment_type_id` pada FuelService, DailySalary, InvoicePurchase, dll.
 *
 * Konsisten dengan opsi PaymentStatusSelectInput.
 */
enum PaymentType: string
{
    case Transfer = '1';
    case Cash = '2';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'transfer',
            self::Cash => 'tunai',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->all();
    }
}

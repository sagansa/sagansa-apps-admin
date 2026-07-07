<?php

namespace App\Enum;

/**
 * Nilai kolom `payment_for` pada tabel `payment_receipts`.
 *
 * Backed enum string agar DB tetap menyimpan '1' / '2' / '3' (tidak ada migrasi),
 * namun kode PHP jadi type-safe.
 */
enum PaymentFor: string
{
    case FuelService = '1';
    case DailySalary = '2';
    case InvoicePurchase = '3';

    /**
     * Label untuk Radio options di form Filament.
     */
    public function label(): string
    {
        return match ($this) {
            self::FuelService => 'fuel/service',
            self::DailySalary => 'daily salary',
            self::InvoicePurchase => 'invoice',
        };
    }

    /**
     * Key tab yang dipakai di ListPaymentReceipts::getTabs().
     * Penting: tab fuel/service pakai spasi ('fuel service'), harus cocok persis.
     */
    public function tabKey(): string
    {
        return match ($this) {
            self::FuelService => 'fuel service',
            self::DailySalary => 'daily salary',
            self::InvoicePurchase => 'invoice',
        };
    }

    /**
     * Reverse lookup dari key tab ListPaymentReceipts ke enum.
     * Mengembalikan null jika tidak dikenal agar caller bisa fallback.
     */
    public static function fromTabKey(?string $tabKey): ?self
    {
        if ($tabKey === null) {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->tabKey() === $tabKey) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Daftar options untuk Radio/Select Filament: [value => label].
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->all();
    }
}

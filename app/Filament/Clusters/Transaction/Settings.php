<?php

namespace App\Filament\Clusters\Transaction;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Data Master';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'transaction/settings';
}

<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Closings extends Cluster
{
    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'transaction/closings';
}

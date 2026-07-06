<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Store extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Toko';

    protected static ?string $navigationLabel = 'Toko';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'store';
}

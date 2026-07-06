<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Asset extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Aset';

    protected static ?string $navigationLabel = 'Aset';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'asset';
}

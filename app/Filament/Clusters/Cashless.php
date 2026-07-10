<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Cashless extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Kas';

    protected static ?string $navigationLabel = 'Cashless';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'cashless';
}

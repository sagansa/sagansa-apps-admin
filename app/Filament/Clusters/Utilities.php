<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Utilities extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Utilitas';

    protected static ?string $navigationLabel = 'Utilitas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'utilities';
}

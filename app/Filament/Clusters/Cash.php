<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Cash extends Cluster
{
    protected static string|\UnitEnum|null $navigationGroup = 'Kas';

    protected static ?string $navigationLabel = 'Bank & Tunai';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'cash';
}

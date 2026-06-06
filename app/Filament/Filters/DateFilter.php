<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setUp();

        $column = $this->getName();

        $this
            ->form([
                DatePicker::make('date_from'),
                DatePicker::make('date_until'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        $data['date_from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                    )
                    ->when(
                        $data['date_until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                    );
            });
    }
}

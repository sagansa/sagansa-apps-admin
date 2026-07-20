<?php

namespace App\Filament\Filters;

use App\Support\ResolvesCreatedBy;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class SelectEmployeeFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('User')
            ->searchable()
            ->preload()
            ->hidden(fn() => !Auth::user()->hasRole('admin'))
            ->relationship('createdBy', 'name', fn(Builder $query) => $query
                ->whereHas('roles', fn(Builder $query) => $query
                    ->whereIn('name', ['staff', 'supervisor', 'former-employee'])))
            ->query(function (Builder $query, array $data) {
                if (filled($data['value'])) {
                    $targetIds = ResolvesCreatedBy::resolveUserIdentifier($data['value']);
                    $query->whereIn('created_by_id', $targetIds);
                }
            });
    }
}

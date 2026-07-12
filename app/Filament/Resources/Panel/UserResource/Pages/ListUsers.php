<?php

namespace App\Filament\Resources\Panel\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\UserResource;
use App\Models\User;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadCsv')
                ->label('Unduh CSV Email')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->action(function () {
                    $headers = [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="user_emails.csv"',
                    ];

                    return response()->streamDownload(function () {
                        $handle = fopen('php://output', 'w');

                        // Stream only user emails (no headers, one email per line)
                        User::select(['email'])
                            ->whereNotNull('email')
                            ->where('email', '!=', '')
                            ->where('email', 'like', '%@gmail.com')
                            ->chunk(100, function ($users) use ($handle) {
                                foreach ($users as $user) {
                                    fputcsv($handle, [$user->email]);
                                }
                            });

                        fclose($handle);
                    }, 'google_console_testers.csv', $headers);
                }),
            Actions\CreateAction::make(),
        ];
    }
}

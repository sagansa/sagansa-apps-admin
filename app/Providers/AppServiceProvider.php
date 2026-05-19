<?php

namespace App\Providers;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if ($this->app->isLocal()) {
            $slowQueryThreshold = (int) env('DB_SLOW_QUERY_LOG_MS', 1000);

            DB::listen(function (QueryExecuted $query) use ($slowQueryThreshold): void {
                if ($query->time < $slowQueryThreshold) {
                    return;
                }

                Log::warning('Slow query detected', [
                    'time_ms' => $query->time,
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'route' => request()?->path(),
                    'url' => request()?->fullUrl(),
                ]);
            });
        }

        // FileUpload::configureUsing(function (FileUpload $fileUpload) {
        //     $fileUpload->hiddenLabel();
        // });

        // TextInput::configureUsing(function (TextInput $textInput) {
        //     $textInput->inlineLabel();
        // });

        Select::configureUsing(function (Select $select) {
            $select->native(false);
        });

        // Radio::configureUsing(function (Radio $radio) {
        //     $radio->inlineLabel();
        // });

        DatePicker::configureUsing(function(DatePicker $datePicker) {
            $datePicker->native(false)->inlineLabel();
        });

        // Section::configureUsing(function(Section $section) {
        //     $section->columns()->compact();
        // });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['id','en']); // also accepts a closure
        });
    }
}

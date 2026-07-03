<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Resources\Panel\RecruitmentApplicantResource\Pages;
use App\Models\ApplicantDetail;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Schemas\Components\Grid as InfoGrid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RecruitmentApplicantResource extends Resource
{
    protected static ?string $model = ApplicantDetail::class;

    protected static ?string $cluster = HRD::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Full Name')
                            ->disabled(),
                        TextInput::make('user.email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Personal Details')
                    ->schema([
                        TextInput::make('nickname')
                            ->label('Nickname')
                            ->disabled(),
                        TextInput::make('nik')
                            ->label('NIK')
                            ->disabled(),
                        TextInput::make('gender')
                            ->disabled(),
                        TextInput::make('birth_place')
                            ->disabled(),
                        DatePicker::make('birth_date')
                            ->disabled(),
                        TextInput::make('religion')
                            ->disabled(),
                        TextInput::make('marital_status')
                            ->disabled(),
                        TextInput::make('children_count')
                            ->label('Children Count')
                            ->disabled(),
                        TextInput::make('education_level')
                            ->disabled(),
                        TextInput::make('education_major')
                            ->disabled(),
                        TextInput::make('driver_license')
                            ->label('Driver License (SIM)')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Address & Family')
                    ->schema([
                        Textarea::make('address')
                            ->disabled()
                            ->columnSpanFull(),
                        TextInput::make('home_location')
                            ->label('Home Location (Koordinat)')
                            ->disabled(),
                        TextInput::make('father_name')
                            ->disabled(),
                        TextInput::make('mother_name')
                            ->disabled(),
                        TextInput::make('emergency_name')
                            ->label('Emergency Contact Name')
                            ->disabled(),
                        TextInput::make('emergency_phone')
                            ->label('Emergency Contact Phone')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Experience Level')
                    ->schema([
                        TextInput::make('is_experienced')
                            ->label('Experience Level')
                            ->formatStateUsing(fn ($state) => $state ? 'Berpengalaman' : 'Fresh Graduate')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Status Penerimaan & Tanggal Bergabung')
                    ->schema([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'reviewed' => 'Reviewed',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),

                        DatePicker::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    /**
     * Action untuk mengembalikan status applicant menjadi "draft" agar pelamar
     * dapat melengkapi/mengubah kembali data yang belum lengkap. Hanya tersedia
     * untuk admin/super-admin (dibatasi oleh ApplicantDetailPolicy) dan hanya
     * ketika status applicant belum "draft".
     */
    public static function revertToDraftAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('revertToDraft')
            ->label('Kembalikan ke Draft')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Kembalikan ke Draft')
            ->modalDescription('Applicant akan dapat melengkapi dan mengubah kembali data pendaftarannya. Lanjutkan?')
            ->visible(fn (ApplicantDetail $record): bool => $record->status !== 'draft')
            ->action(function (ApplicantDetail $record) {
                $record->update(['status' => 'draft']);

                Notification::make()
                    ->title('Status dikembalikan ke Draft')
                    ->success()
                    ->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('education_level')
                    ->label('Education'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'reviewed' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('join_date')
                    ->label('Join Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                static::revertToDraftAction(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('revertToDraftBulk')
                        ->label('Kembalikan ke Draft')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Kembalikan ke Draft')
                        ->modalDescription('Applicant terpilih akan dapat melengkapi kembali data pendaftarannya. Lanjutkan?')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $count = 0;
                            $records->each(function (ApplicantDetail $record) use (&$count) {
                                if ($record->status !== 'draft') {
                                    $record->update(['status' => 'draft']);
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->title($count . ' applicant dikembalikan ke Draft')
                                ->success()
                                ->send();
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                InfoSection::make('Applicant Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Full Name'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('phone')
                            ->label('Phone Number'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted' => 'warning',
                                'reviewed' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),

                InfoSection::make('Personal Details')
                    ->schema([
                        TextEntry::make('nickname')
                            ->label('Nickname'),
                        TextEntry::make('nik')
                            ->label('NIK'),
                        TextEntry::make('gender'),
                        TextEntry::make('birth_place')
                            ->label('Birth Place'),
                        TextEntry::make('birth_date')
                            ->date(),
                        TextEntry::make('religion'),
                        TextEntry::make('marital_status'),
                        TextEntry::make('children_count')
                            ->label('Children Count')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record && $record->marital_status !== 'single'),
                        TextEntry::make('education_level')
                            ->label('Education Level'),
                        TextEntry::make('education_major')
                            ->label('Education Major'),
                        TextEntry::make('driver_license')
                            ->label('Driver License (SIM)')
                            ->placeholder('-'),
                    ])->columns(2),

                InfoSection::make('Address & Family')
                    ->schema([
                        TextEntry::make('address')
                            ->columnSpanFull(),
                        TextEntry::make('home_location')
                            ->label('Home Location (Koordinat)')
                            ->placeholder('-')
                            ->url(fn ($state) => $state && str_contains($state, ',')
                                ? 'https://www.google.com/maps?q=' . rawurlencode($state)
                                : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('father_name'),
                        TextEntry::make('mother_name'),
                        TextEntry::make('emergency_name')
                            ->label('Emergency Contact Name'),
                        TextEntry::make('emergency_phone')
                            ->label('Emergency Contact Phone'),
                    ])->columns(2),

                InfoSection::make('Experience Level')
                    ->schema([
                        TextEntry::make('is_experienced')
                            ->label('Experience Level')
                            ->formatStateUsing(fn ($state) => $state ? 'Berpengalaman' : 'Fresh Graduate'),
                    ])->columns(2),

                InfoSection::make('Work Experiences')
                    ->schema([
                        RepeatableEntry::make('user.workExperiences')
                            ->label('Experience Records')
                            ->placeholder('Belum ada pengalaman kerja')
                            ->schema([
                                TextEntry::make('company_name')
                                    ->weight('bold'),
                                TextEntry::make('position'),
                                TextEntry::make('salary')
                                    ->money('IDR'),
                                TextEntry::make('start_date')
                                    ->date(),
                                TextEntry::make('end_date')
                                    ->date(),
                                TextEntry::make('supervisor_name')
                                    ->label('Supervisor'),
                                TextEntry::make('supervisor_phone')
                                    ->label('Supervisor Phone')
                                    ->placeholder('-'),
                                TextEntry::make('is_contactable')
                                    ->label('Contactable')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                                TextEntry::make('description')
                                    ->columnSpanFull(),
                            ])->columns(3)
                    ]),

                InfoSection::make('Documents')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                ImageEntry::make('ktp_image')
                                    ->label('KTP Image')
                                    ->disk('public'),
                                ImageEntry::make('selfie_image')
                                    ->label('Selfie Image')
                                    ->disk('public'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentApplicants::route('/'),
            'view' => Pages\ViewRecruitmentApplicant::route('/{record}'),
        ];
    }
}

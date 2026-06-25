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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                        TextInput::make('education_level')
                            ->disabled(),
                        TextInput::make('education_major')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Address & Family')
                    ->schema([
                        Textarea::make('address')
                            ->disabled()
                            ->columnSpanFull(),
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
            ]);
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
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
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
                        TextEntry::make('nik')
                            ->label('NIK'),
                        TextEntry::make('gender'),
                        TextEntry::make('birth_place'),
                        TextEntry::make('birth_date')
                            ->date(),
                        TextEntry::make('religion'),
                        TextEntry::make('marital_status'),
                        TextEntry::make('education_level'),
                        TextEntry::make('education_major'),
                    ])->columns(2),

                InfoSection::make('Work Experiences')
                    ->schema([
                        RepeatableEntry::make('user.workExperiences')
                            ->label('Experience Records')
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

<?php

namespace App\Filament\Resources\Panel\RecruitmentApplicantResource\Pages;

use App\Filament\Resources\Panel\RecruitmentApplicantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecruitmentApplicant extends ViewRecord
{
    protected static string $resource = RecruitmentApplicantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RecruitmentApplicantResource::approveAction(),
            RecruitmentApplicantResource::rejectAction(),
            RecruitmentApplicantResource::revertToDraftAction(),
        ];
    }
}

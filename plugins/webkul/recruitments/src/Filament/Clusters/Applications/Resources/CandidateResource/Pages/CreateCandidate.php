<?php

namespace Webkul\Recruitment\Filament\Clusters\Applications\Resources\CandidateResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CandidateResource;

/**
 * Create Candidate class
 *
 * @see \Filament\Resources\Resource
 */
class CreateCandidate extends CreateRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('recruitments::filament/clusters/applications/resources/candidate/pages/create-candidate.notification.title'))
            ->body(__('recruitments::filament/clusters/applications/resources/candidate/pages/create-candidate.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['creator_id'] = $user->id;
        $data['company_id'] = $user?->default_company_id;

        return $data;
    }
}

<?php

namespace App\Filament\Resources\ApiTokenResource\Pages;

use App\Filament\Resources\ApiTokenResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Models\User;

class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected ?string $plainTextToken = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // We'll handle the token creation ourselves
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Get the user
        $user = User::find($data['tokenable_id'] ?? Auth::id());

        // Create the token using Sanctum
        $token = $user->createToken(
            $data['name'],
            $data['abilities'] ?? ['*'],
            $data['expires_at'] ?? null
        );

        // Store the plain text token to show the user
        $this->plainTextToken = $token->plainTextToken;

        return $token->accessToken;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('API Token Created')
            ->body('Copy your token now - it won\'t be shown again!')
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to the view page with the token displayed
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
            'token' => $this->plainTextToken,
        ]);
    }
}

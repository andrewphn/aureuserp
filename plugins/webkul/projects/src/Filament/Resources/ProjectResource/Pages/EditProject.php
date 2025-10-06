<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\ActivityPlan;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;


    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.pages.project-sticky-footer', ['page' => $this]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load project address from database if it exists
        if ($this->record->addresses()->count() > 0) {
            $address = $this->record->addresses()->where('is_primary', true)->first()
                       ?? $this->record->addresses()->first();

            $data['project_address'] = [
                'street1' => $address->street1,
                'street2' => $address->street2,
                'city' => $address->city,
                'zip' => $address->zip,
                'country_id' => $address->country_id,
                'state_id' => $address->state_id,
            ];

            // Set use_customer_address to false since we have a project-specific address
            $data['use_customer_address'] = false;
        }

        return $data;
    }

    public function deletePdf($pdfId)
    {
        $pdf = $this->record->pdfDocuments()->find($pdfId);

        if ($pdf) {
            // Delete the file from storage
            if (Storage::disk('public')->exists($pdf->file_path)) {
                Storage::disk('public')->delete($pdf->file_path);
            }

            // Delete the database record
            $pdf->delete();

            Notification::make()
                ->success()
                ->title('PDF deleted successfully')
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title('PDF not found')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/project/pages/edit-project.notification.title'))
            ->body(__('projects::filament/resources/project/pages/edit-project.notification.body'));
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // Update or create project address
        if (!empty($data['project_address'])) {
            $addressData = $data['project_address'];

            if (!empty($addressData['street1']) || !empty($addressData['city'])) {
                // Update existing or create new address
                $address = $this->record->addresses()->where('is_primary', true)->first()
                           ?? $this->record->addresses()->first();

                if ($address) {
                    $address->update([
                        'street1' => $addressData['street1'] ?? null,
                        'street2' => $addressData['street2'] ?? null,
                        'city' => $addressData['city'] ?? null,
                        'zip' => $addressData['zip'] ?? null,
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                    ]);
                } else {
                    $this->record->addresses()->create([
                        'type' => 'project',
                        'street1' => $addressData['street1'] ?? null,
                        'street2' => $addressData['street2'] ?? null,
                        'city' => $addressData['city'] ?? null,
                        'zip' => $addressData['zip'] ?? null,
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                        'is_primary' => true,
                    ]);
                }
            }
        }

        // PDF uploads are now handled exclusively through the Upload PDFs modal action
        // This ensures all PDFs are uploaded with proper metadata (document_type, notes)
    }


    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource)
                ->setActivityPlans($this->getActivityPlans()),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('projects::filament/resources/project/pages/edit-project.header-actions.delete.notification.title'))
                        ->body(__('projects::filament/resources/project/pages/edit-project.header-actions.delete.notification.body')),
                ),
        ];
    }

    private function getActivityPlans(): mixed
    {
        return ActivityPlan::where('plugin', 'projects')->pluck('name', 'id');
    }
}

<?php

namespace Webkul\Project\Filament\Resources\CabinetReportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Project\Filament\Resources\CabinetReportResource;

class ViewCabinetReport extends ViewRecord
{
    protected static string $resource = CabinetReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('find_similar')
                ->label('Find Similar Cabinets')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->url(fn () => route('filament.admin.inventory.products.resources.cabinet-reports.index', [
                    'tableFilters' => [
                        'length_range' => [
                            'min' => $this->record->length_inches - 2,
                            'max' => $this->record->length_inches + 2,
                        ],
                    ],
                ])),

            Actions\Action::make('use_as_template')
                ->label('Use as Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create Template from This Cabinet')
                ->modalDescription('This will save this cabinet configuration as a reusable template.')
                ->action(function () {
                    // Future: Create template logic
                    $this->notify('success', 'Template feature coming soon!');
                }),

            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Cabinet Specification #' . $this->record->id;
    }
}

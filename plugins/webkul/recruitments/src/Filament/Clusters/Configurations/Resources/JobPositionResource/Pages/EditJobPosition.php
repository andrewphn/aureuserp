<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages\EditJobPosition as BaseEditJobPosition;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource;

/**
 * Edit Job Position class
 *
 * @see \Filament\Resources\Resource
 */
class EditJobPosition extends BaseEditJobPosition
{
    protected static string $resource = JobPositionResource::class;

    /**
     * Mutate Form Data Before Fill
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->prepareData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareData($data);
    }

    /**
     * After Save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        $this->record->refresh();
    }

    /**
     * Prepare Data
     *
     * @param mixed $data The data array
     * @return array
     */
    public function prepareData($data): array
    {
        $model = $this->record;

        return array_merge($data, [
            'no_of_employee'       => $model->no_of_employee,
            'no_of_hired_employee' => $model->no_of_hired_employee,
            'expected_employees'   => $model->expected_employees,
        ]);
    }
}

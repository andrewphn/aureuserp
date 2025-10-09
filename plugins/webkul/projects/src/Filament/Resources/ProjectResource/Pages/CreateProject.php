<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\Company;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public static bool $formActionsAreSticky = true;

    public function mount(): void
    {
        // Create a draft project immediately and redirect to edit
        $defaultCompany = Company::where('is_default', true)->first();

        // Get the first project stage (To Do)
        $initialStage = \Webkul\Project\Models\ProjectStage::orderBy('sort')->first();

        $project = \Webkul\Project\Models\Project::create([
            'name' => 'New Project (Draft)',
            'company_id' => $defaultCompany?->id,
            'creator_id' => Auth::id(),
            'project_number' => $this->generateDraftProjectNumber($defaultCompany?->id),
            'stage_id' => $initialStage?->id,
            'visibility' => 'internal',
            'allow_milestones' => true,
        ]);

        // Redirect to edit page
        $this->redirect(ProjectResource::getUrl('edit', ['record' => $project->id]));
    }

    protected function generateDraftProjectNumber(?int $companyId): string
    {
        $companyAcronym = 'UNK';
        if ($companyId) {
            $company = Company::find($companyId);
            $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'UNK', 0, 3));
        }

        // Get next sequential number for drafts
        $lastProject = \Webkul\Project\Models\Project::where('company_id', $companyId)
            ->where('project_number', 'like', "{$companyAcronym}-DRAFT-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = 1;
        if ($lastProject && $lastProject->project_number) {
            preg_match('/-DRAFT-(\d+)$/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = intval($matches[1]) + 1;
            }
        }

        return sprintf('%s-DRAFT-%04d', $companyAcronym, $sequentialNumber);
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.pages.project-sticky-footer', ['page' => $this]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/project/pages/create-project.notification.title'))
            ->body(__('projects::filament/resources/project/pages/create-project.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();

        // Auto-generate project number if not provided
        if (empty($data['project_number'])) {
            $data['project_number'] = $this->generateProjectNumber($data);
        }

        // Auto-populate allocated_hours from production estimate if available
        if (!empty($data['estimated_linear_feet']) && !empty($data['company_id'])) {
            $estimate = \App\Services\ProductionEstimatorService::calculate(
                $data['estimated_linear_feet'],
                $data['company_id']
            );

            if ($estimate && empty($data['allocated_hours'])) {
                $data['allocated_hours'] = $estimate['hours'];
            }
        }

        return $data;
    }

    protected function generateProjectNumber(array $data): string
    {
        // Get company acronym
        $companyAcronym = 'UNK';
        if (!empty($data['company_id'])) {
            $company = \Webkul\Support\Models\Company::find($data['company_id']);
            $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'UNK', 0, 3));
        }

        // Get next sequential number for this company
        $lastProject = \Webkul\Project\Models\Project::where('company_id', $data['company_id'])
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = 1;
        if ($lastProject && $lastProject->project_number) {
            // Extract number from format: TCS-0001-Street
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = intval($matches[1]) + 1;
            }
        }

        // Get street address (remove spaces and special chars)
        $streetAbbr = '';
        if (!empty($data['project_address']['street1'])) {
            $street = preg_replace('/[^a-zA-Z0-9]/', '', $data['project_address']['street1']);
            $streetAbbr = $street;
        }

        // Format: TCS-0001-15BCorreiaLane
        return sprintf(
            '%s-%04d%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr ? "-{$streetAbbr}" : ''
        );
    }

    protected function afterCreate(): void
    {
        $project = $this->record;
        $data = $this->form->getState();

        // Save project address if provided
        if (!empty($data['project_address'])) {
            $addressData = $data['project_address'];

            if (!empty($addressData['street1']) || !empty($addressData['city'])) {
                $project->addresses()->create([
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

        // Save production estimate if linear feet and company are provided
        if (!empty($data['estimated_linear_feet']) && !empty($data['company_id'])) {
            $estimate = \App\Services\ProductionEstimatorService::calculate(
                $data['estimated_linear_feet'],
                $data['company_id']
            );

            if ($estimate) {
                \App\Models\ProductionEstimate::createFromEstimate(
                    $project->id,
                    $data['company_id'],
                    $data['estimated_linear_feet'],
                    $estimate
                );
            }
        }

        // Save architectural PDFs if uploaded
        if (!empty($data['architectural_pdfs'])) {
            $revisionNumber = 1;

            foreach ($data['architectural_pdfs'] as $index => $pdfPath) {
                $originalFilename = basename($pdfPath);
                $fileSize = \Storage::disk('public')->size($pdfPath);

                // Generate proper filename: ProjectNumber-RevX-OriginalName.pdf
                // Example: TCS-0001-15BCorreiaLane-Rev1-FloorPlan.pdf
                $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                $originalName = pathinfo($originalFilename, PATHINFO_FILENAME);

                // Clean up the original filename (remove hash if present from FileUpload)
                // Filament adds a unique hash to filenames, let's strip that if it exists
                $cleanOriginalName = preg_replace('/^[0-9A-Z]{26}_/', '', $originalName);

                $newFilename = sprintf(
                    '%s-Rev%d-%s.%s',
                    $project->project_number,
                    $revisionNumber,
                    $cleanOriginalName ?: 'Drawing',
                    $extension
                );

                // Build new path in same directory
                $directory = dirname($pdfPath);
                $newPath = $directory . '/' . $newFilename;

                // Rename the actual file in storage
                \Storage::disk('public')->move($pdfPath, $newPath);

                // Extract page count from PDF using proper parser
                $pageCount = null;
                try {
                    $fullPath = \Storage::disk('public')->path($newPath);
                    if (file_exists($fullPath)) {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($fullPath);
                        $pages = $pdf->getPages();
                        $pageCount = count($pages);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
                }

                $project->pdfDocuments()->create([
                    'file_path' => $newPath,
                    'file_name' => $newFilename,
                    'file_size' => $fileSize,
                    'mime_type' => 'application/pdf',
                    'document_type' => 'drawing',
                    'page_count' => $pageCount,
                    'uploaded_by' => Auth::id(),
                    'metadata' => json_encode([
                        'revision' => $revisionNumber,
                        'original_filename' => $originalFilename,
                    ]),
                ]);

                $revisionNumber++;
            }
        }
    }
}

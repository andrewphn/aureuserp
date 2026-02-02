<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Services\GeminiTaskTemplateService;

/**
 * Reusable action to generate AI task suggestions for milestone templates.
 */
class GenerateTasksWithAiAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generate-ai-tasks';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('primary')
            ->icon('heroicon-o-sparkles')
            ->label('Generate Tasks with AI')
            ->modalHeading('Generate Task Templates with AI')
            ->modalDescription(fn (MilestoneTemplate $record) => "AI will suggest tasks based on the \"{$record->name}\" milestone type and its production stage ({$record->production_stage}).")
            ->modalIcon('heroicon-o-sparkles')
            ->modalIconColor('primary')
            ->modalSubmitActionLabel('Generate Suggestions')
            ->form(function (MilestoneTemplate $record) {
                $hasDescription = !empty($record->description);

                $fields = [];

                // Show current description or warning if none
                if ($hasDescription) {
                    $fields[] = \Filament\Forms\Components\Placeholder::make('current_description')
                        ->label('Milestone Description')
                        ->content($record->description)
                        ->helperText('The AI will use this description to generate relevant tasks.');
                } else {
                    $fields[] = \Filament\Forms\Components\Placeholder::make('no_description_warning')
                        ->label('No Description Set')
                        ->content('This milestone has no description. Please provide context below so the AI knows what tasks to generate.')
                        ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400']);
                }

                $fields[] = Textarea::make('additional_context')
                    ->label($hasDescription ? 'Additional Context (Optional)' : 'Task Context (Recommended)')
                    ->placeholder($hasDescription
                        ? 'e.g., This milestone should include QC checkpoints, focus on finishing tasks, etc.'
                        : 'e.g., This milestone covers cabinet assembly - include tasks for rough assembly, glue-up, squaring, and clamping...')
                    ->helperText($hasDescription
                        ? 'Provide any specific requirements or focus areas for the AI to consider.'
                        : 'Since there\'s no description, this context will be the primary guide for task generation.')
                    ->rows($hasDescription ? 3 : 5)
                    ->required(!$hasDescription);

                return $fields;
            })
            ->action(function (MilestoneTemplate $record, array $data, $livewire) {
                try {
                    /** @var GeminiTaskTemplateService $service */
                    $service = app(GeminiTaskTemplateService::class);

                    $suggestion = $service->generateTaskSuggestions(
                        $record,
                        $data['additional_context'] ?? null
                    );

                    $taskCount = $suggestion->suggested_task_count;
                    $confidence = $suggestion->confidence_level;

                    Notification::make()
                        ->success()
                        ->title('AI Generated Suggestions')
                        ->body("Generated {$taskCount} task suggestions with {$confidence} confidence. Redirecting to review...")
                        ->send();

                    // Redirect to review page with suggestion ID as query param
                    return redirect()->to(
                        MilestoneTemplateResource::getUrl('review-ai', [
                            'record' => $record,
                        ]) . '?suggestionId=' . $suggestion->id
                    );
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Generation Failed')
                        ->body('Failed to generate AI suggestions: ' . $e->getMessage())
                        ->send();

                    return null;
                }
            });
    }
}

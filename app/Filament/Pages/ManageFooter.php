<?php

namespace App\Filament\Pages;

use App\Services\FooterFieldRegistry;
use App\Services\FooterPreferenceService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ManageFooter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.manage-footer';

    protected static ?string $navigationLabel = 'Footer Customizer';

    protected static ?string $title = 'Footer Customizer';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public $templates = [];

    public function mount(): void
    {
        $this->loadUserPreferences();
        $this->loadTemplates();
    }

    protected function loadTemplates(): void
    {
        $service = app(FooterPreferenceService::class);
        $this->templates = $service->getAvailableTemplates()->toArray();
    }

    protected function loadUserPreferences(): void
    {
        $service = app(FooterPreferenceService::class);
        $user = auth()->user();

        $preferences = $service->getAllUserPreferences($user);

        $this->data = [
            'project_minimized' => $preferences['project']['minimized_fields'] ?? [],
            'project_expanded' => $preferences['project']['expanded_fields'] ?? [],
            'sale_minimized' => $preferences['sale']['minimized_fields'] ?? [],
            'sale_expanded' => $preferences['sale']['expanded_fields'] ?? [],
            'inventory_minimized' => $preferences['inventory']['minimized_fields'] ?? [],
            'inventory_expanded' => $preferences['inventory']['expanded_fields'] ?? [],
            'production_minimized' => $preferences['production']['minimized_fields'] ?? [],
            'production_expanded' => $preferences['production']['expanded_fields'] ?? [],
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $registry = app(FooterFieldRegistry::class);

        return $schema
            ->components([
                Tabs::make('Context Settings')
                    ->tabs([
                        Tabs\Tab::make('Projects')
                            ->icon('heroicon-o-briefcase')
                            ->schema($this->getContextFieldsSchema('project', $registry)),

                        Tabs\Tab::make('Sales')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema($this->getContextFieldsSchema('sale', $registry)),

                        Tabs\Tab::make('Inventory')
                            ->icon('heroicon-o-cube')
                            ->schema($this->getContextFieldsSchema('inventory', $registry)),

                        Tabs\Tab::make('Production')
                            ->icon('heroicon-o-cog')
                            ->schema($this->getContextFieldsSchema('production', $registry)),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getContextFieldsSchema(string $contextType, FooterFieldRegistry $registry): array
    {
        $fields = $registry->getAvailableFields($contextType);

        $options = collect($fields)->mapWithKeys(function ($field, $key) {
            return [$key => $field['label'] . ' (' . $field['type'] . ')'];
        })->toArray();

        return [
            Section::make('Minimized View')
                ->description('Fields shown when footer is collapsed (2-3 recommended)')
                ->schema([
                    CheckboxList::make("{$contextType}_minimized")
                        ->label('Select Fields')
                        ->options($options)
                        ->columns(2)
                        ->gridDirection('row')
                        ->bulkToggleable()
                        ->searchable(),
                ]),

            Section::make('Expanded View')
                ->description('Fields shown when footer is expanded (5-10 recommended)')
                ->schema([
                    CheckboxList::make("{$contextType}_expanded")
                        ->label('Select Fields')
                        ->options($options)
                        ->columns(3)
                        ->gridDirection('row')
                        ->bulkToggleable()
                        ->searchable(),
                ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Preferences')
                ->action('savePreferences')
                ->color('primary'),

            Action::make('reset')
                ->label('Reset to Defaults')
                ->action('resetToDefaults')
                ->color('danger')
                ->outlined()
                ->requiresConfirmation(),
        ];
    }

    /**
     * Apply a template by slug (called from blade view)
     */
    public function applyTemplate(string $slug): void
    {
        $this->applyPersonaTemplate($slug);
    }

    public function savePreferences(): void
    {
        try {
            // Get form data from Livewire's $data property instead
            $data = $this->data;
            $service = app(FooterPreferenceService::class);
            $user = auth()->user();

            // Save each context's preferences
            $contexts = ['project', 'sale', 'inventory', 'production'];
            foreach ($contexts as $context) {
                $service->saveUserPreferences($user, $context, [
                    'minimized_fields' => $data["{$context}_minimized"] ?? [],
                    'expanded_fields' => $data["{$context}_expanded"] ?? [],
                    'field_order' => [],
                ]);
            }

            Notification::make()
                ->title('Preferences Saved')
                ->body('Your footer preferences have been saved successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Preferences')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    public function applyPersonaTemplate(string $persona = 'owner'): void
    {
        $service = app(FooterPreferenceService::class);
        $user = auth()->user();

        $applied = $service->applyPersonaTemplate($user, $persona);

        $this->loadUserPreferences();

        Notification::make()
            ->title('Template Applied')
            ->body("Applied {$persona} template to " . count($applied) . ' contexts')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        $service = app(FooterPreferenceService::class);
        $user = auth()->user();

        $contexts = ['project', 'sale', 'inventory', 'production'];
        foreach ($contexts as $context) {
            $service->resetToDefaults($user, $context);
        }

        $this->loadUserPreferences();

        Notification::make()
            ->title('Reset to Defaults')
            ->body('All footer preferences have been reset to default values')
            ->success()
            ->send();
    }
}

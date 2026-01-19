<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Schemas\Components\KeyValue;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\CabinetCalculationAuditResource\Pages;
use Webkul\Project\Models\CabinetCalculationAudit;

class CabinetCalculationAuditResource extends Resource
{
    protected static ?string $model = CabinetCalculationAudit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Construction Standards';

    public static function getNavigationLabel(): string
    {
        return 'Calculation Audits';
    }

    public static function getModelLabel(): string
    {
        return 'Calculation Audit';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CabinetCalculationAudit::query()->needsAttention()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Audit Overview')
                ->columns(3)
                ->schema([
                    Placeholder::make('cabinet')
                        ->label('Cabinet')
                        ->content(fn ($record) => $record->cabinet?->full_code ?? 'N/A'),
                    Placeholder::make('audit_type')
                        ->label('Audit Type')
                        ->content(fn ($record) => $record->type_label),
                    Placeholder::make('audit_status')
                        ->label('Status')
                        ->content(fn ($record) => $record->status_label),
                    Placeholder::make('template')
                        ->label('Construction Template')
                        ->content(fn ($record) => $record->constructionTemplate?->name ?? 'Fallback Defaults'),
                    Placeholder::make('audited_by')
                        ->label('Audited By')
                        ->content(fn ($record) => $record->auditedBy?->name ?? 'System'),
                    Placeholder::make('created_at')
                        ->label('Audit Date')
                        ->content(fn ($record) => $record->created_at?->format('M j, Y g:i A')),
                ]),

            Section::make('Discrepancy Summary')
                ->columns(3)
                ->schema([
                    Placeholder::make('discrepancy_count')
                        ->label('Discrepancies Found')
                        ->content(fn ($record) => $record->discrepancy_count),
                    Placeholder::make('max_discrepancy')
                        ->label('Maximum Discrepancy')
                        ->content(fn ($record) => $record->max_discrepancy_inches
                            ? sprintf('%.4f" (%s)', $record->max_discrepancy_inches, $record->max_discrepancy_field)
                            : 'None'),
                    Placeholder::make('summary')
                        ->label('Summary')
                        ->content(fn ($record) => $record->discrepancy_summary),
                ]),

            Section::make('Value Comparison')
                ->columns(2)
                ->schema([
                    KeyValue::make('stored_values')
                        ->label('Stored Values (Cabinet)')
                        ->disabled(),
                    KeyValue::make('calculated_values')
                        ->label('Calculated Values (Expected)')
                        ->disabled(),
                ]),

            Section::make('Discrepancy Details')
                ->collapsed(fn ($record) => $record->discrepancy_count === 0)
                ->schema([
                    Placeholder::make('discrepancies_list')
                        ->label('')
                        ->content(function ($record) {
                            if (empty($record->discrepancies)) {
                                return 'No discrepancies found.';
                            }
                            $html = '<ul class="list-disc pl-5 space-y-1">';
                            foreach ($record->discrepancies as $d) {
                                $color = match ($d['severity'] ?? 'info') {
                                    'error' => 'text-danger-600',
                                    'warning' => 'text-warning-600',
                                    default => 'text-gray-600',
                                };
                                $html .= sprintf(
                                    '<li class="%s">%s</li>',
                                    $color,
                                    htmlspecialchars($d['message'])
                                );
                            }
                            $html .= '</ul>';
                            return new \Illuminate\Support\HtmlString($html);
                        }),
                ]),

            Section::make('Override Information')
                ->visible(fn ($record) => $record->is_overridden)
                ->schema([
                    Placeholder::make('override_by')
                        ->label('Overridden By')
                        ->content(fn ($record) => $record->overrideBy?->name ?? 'Unknown'),
                    Placeholder::make('override_at')
                        ->label('Override Date')
                        ->content(fn ($record) => $record->override_at?->format('M j, Y g:i A')),
                    Textarea::make('override_reason')
                        ->label('Override Reason')
                        ->disabled(),
                ]),

            Section::make('Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->disabled(),
                    Placeholder::make('trigger_source')
                        ->label('Trigger Source')
                        ->content(fn ($record) => $record->trigger_source ?? 'Manual'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cabinet.full_code')
                    ->label('Cabinet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('audit_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->type_label),
                IconColumn::make('audit_status')
                    ->label('Status')
                    ->icon(fn ($state) => match ($state) {
                        'passed' => 'heroicon-o-check-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'failed' => 'heroicon-o-x-circle',
                        'override' => 'heroicon-o-hand-raised',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'passed' => 'success',
                        'warning' => 'warning',
                        'failed' => 'danger',
                        'override' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('discrepancy_count')
                    ->label('Discrepancies')
                    ->sortable(),
                TextColumn::make('max_discrepancy_inches')
                    ->label('Max Diff')
                    ->suffix('"')
                    ->numeric(4)
                    ->sortable(),
                TextColumn::make('constructionTemplate.name')
                    ->label('Template')
                    ->placeholder('Fallback'),
                TextColumn::make('created_at')
                    ->label('Audited')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('audit_status')
                    ->label('Status')
                    ->options(CabinetCalculationAudit::auditStatusOptions()),
                SelectFilter::make('audit_type')
                    ->label('Type')
                    ->options(CabinetCalculationAudit::auditTypeOptions()),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCabinetCalculationAudits::route('/'),
            'view' => Pages\ViewCabinetCalculationAudit::route('/{record}'),
        ];
    }
}

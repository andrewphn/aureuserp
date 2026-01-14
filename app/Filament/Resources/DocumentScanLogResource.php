<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentScanLogResource\Pages\ListDocumentScanLogs;
use App\Filament\Resources\DocumentScanLogResource\Pages\ViewDocumentScanLog;
use App\Models\DocumentScanLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Document scan QC resource.
 */
class DocumentScanLogResource extends Resource
{
    protected static ?string $model = DocumentScanLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Document Scans';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Scanned At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DocumentScanLog::STATUS_PENDING_REVIEW => 'warning',
                        DocumentScanLog::STATUS_APPROVED => 'success',
                        DocumentScanLog::STATUS_REJECTED => 'danger',
                        DocumentScanLog::STATUS_AUTO_APPLIED => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('overall_confidence')
                    ->label('Confidence')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                IconColumn::make('vendor_matched')
                    ->label('Vendor')
                    ->boolean(),
                IconColumn::make('po_matched')
                    ->label('PO')
                    ->boolean(),
                TextColumn::make('lines_total_count')
                    ->label('Lines')
                    ->sortable(),
                TextColumn::make('lines_unmatched_count')
                    ->label('Unmatched')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        DocumentScanLog::STATUS_PENDING_REVIEW => 'Pending Review',
                        DocumentScanLog::STATUS_APPROVED => 'Approved',
                        DocumentScanLog::STATUS_REJECTED => 'Rejected',
                        DocumentScanLog::STATUS_AUTO_APPLIED => 'Auto Applied',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->visible(fn (DocumentScanLog $record) => $record->status === DocumentScanLog::STATUS_PENDING_REVIEW)
                    ->requiresConfirmation()
                    ->action(fn (DocumentScanLog $record) => $record->approve()),
                Action::make('reject')
                    ->label('Reject')
                    ->visible(fn (DocumentScanLog $record) => $record->status === DocumentScanLog::STATUS_PENDING_REVIEW)
                    ->requiresConfirmation()
                    ->action(fn (DocumentScanLog $record) => $record->reject()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scan Details')
                    ->schema([
                        TextEntry::make('document_type')->label('Type'),
                        TextEntry::make('status')->label('Status'),
                        TextEntry::make('overall_confidence')->label('Confidence'),
                        TextEntry::make('vendor_matched')->label('Vendor Matched'),
                        TextEntry::make('po_matched')->label('PO Matched'),
                        TextEntry::make('lines_total_count')->label('Lines Total'),
                        TextEntry::make('lines_unmatched_count')->label('Lines Unmatched'),
                        TextEntry::make('original_filename')->label('Original Filename'),
                        TextEntry::make('file_path')->label('Stored Path'),
                        TextEntry::make('created_at')->label('Created At')->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Extracted Data')
                    ->schema([
                        TextEntry::make('extracted_data')
                            ->label('Extracted JSON')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null)
                            ->columnSpanFull(),
                    ]),
                Section::make('Raw AI Response')
                    ->schema([
                        TextEntry::make('raw_ai_response')
                            ->label('Raw Response')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentScanLogs::route('/'),
            'view' => ViewDocumentScanLog::route('/{record}'),
        ];
    }
}

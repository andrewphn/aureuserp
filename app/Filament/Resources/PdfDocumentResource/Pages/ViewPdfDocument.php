<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * View Pdf Document class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPdfDocument extends ViewRecord
{
    protected static string $resource = PdfDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Define the infolist schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\Section::make('Document Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('file_path')
                            ->label('File')
                            ->formatStateUsing(fn ($state) => basename($state))
                            ->url(fn ($record) => Storage::url($record->file_path))
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('file_size')
                            ->label('Size')
                            ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),

                        Infolists\Components\TextEntry::make('page_count')
                            ->label('Pages')
                            ->default('N/A'),

                        Infolists\Components\TextEntry::make('mime_type')
                            ->label('Type')
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Classification')
                    ->schema([
                        Infolists\Components\TextEntry::make('folder.name')
                            ->label('Folder')
                            ->badge()
                            ->color('gray')
                            ->default('None'),

                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color(fn ($record) => $record->category?->color ?? 'primary')
                            ->default('None'),

                        Infolists\Components\TextEntry::make('documentable_type')
                            ->label('Related To')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'None')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('tags')
                            ->badge()
                            ->separator(',')
                            ->default('None'),

                        Infolists\Components\IconEntry::make('is_public')
                            ->label('Public')
                            ->boolean(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('uploader.name')
                            ->label('Uploaded By'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Modified')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}

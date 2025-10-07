<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Schemas\Schema;

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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Section::make('Document Information')
                    ->components([
                        Components\TextEntry::make('title')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Components\TextEntry::make('description')
                            ->columnSpanFull(),

                        Components\TextEntry::make('file_path')
                            ->label('File')
                            ->formatStateUsing(fn ($state) => basename($state))
                            ->url(fn ($record) => Storage::url($record->file_path))
                            ->openUrlInNewTab(),

                        Components\TextEntry::make('file_size')
                            ->label('Size')
                            ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),

                        Components\TextEntry::make('page_count')
                            ->label('Pages')
                            ->default('N/A'),

                        Components\TextEntry::make('mime_type')
                            ->label('Type')
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(3),

                Components\Section::make('Classification')
                    ->components([
                        Components\TextEntry::make('folder.name')
                            ->label('Folder')
                            ->badge()
                            ->color('gray')
                            ->default('None'),

                        Components\TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color(fn ($record) => $record->category?->color ?? 'primary')
                            ->default('None'),

                        Components\TextEntry::make('documentable_type')
                            ->label('Related To')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'None')
                            ->badge()
                            ->color('success'),

                        Components\TextEntry::make('tags')
                            ->badge()
                            ->separator(',')
                            ->default('None'),

                        Components\IconEntry::make('is_public')
                            ->label('Public')
                            ->boolean(),
                    ])
                    ->columns(3),

                Components\Section::make('Metadata')
                    ->components([
                        Components\TextEntry::make('uploader.name')
                            ->label('Uploaded By'),

                        Components\TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),

                        Components\TextEntry::make('updated_at')
                            ->label('Last Modified')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}

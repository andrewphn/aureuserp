<?php

namespace App\Filament\Resources\RhinoExtractionReviewResource\Pages;

use App\Filament\Resources\RhinoExtractionReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListRhinoExtractionReviews extends ListRecords
{
    protected static string $resource = RhinoExtractionReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

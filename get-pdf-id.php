<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pdf = \App\Models\PdfDocument::first();

if ($pdf) {
    echo "PDF ID: " . $pdf->id . "\n";

    // Try to find project
    $project = \Webkul\Project\Models\Project::where('name', 'LIKE', '%25 Friendship%')->first();
    if ($project) {
        echo "Project ID: " . $project->id . "\n";
        echo "Project Name: " . $project->name . "\n";
    } else {
        $project = \Webkul\Project\Models\Project::first();
        if ($project) {
            echo "Project ID (first): " . $project->id . "\n";
            echo "Project Name: " . $project->name . "\n";
        }
    }

    echo "File Name: " . $pdf->file_name . "\n";
} else {
    echo "No PDF documents found\n";
}

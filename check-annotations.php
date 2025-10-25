<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🔍 ALL Annotations on PDF Page 2:\n";
echo str_repeat('=', 150) . "\n";
echo sprintf("%-4s | %-25s | %-15s | %-12s | %-10s | %-12s | %-15s | %-15s\n",
    "ID", "Label", "Type", "Parent ID", "Room ID", "Location ID", "Cabinet Run ID", "Cabinet ID");
echo str_repeat('=', 150) . "\n";

$annotations = \App\Models\PdfPageAnnotation::where('pdf_page_id', 2)
    ->orderBy('id')
    ->get();

foreach ($annotations as $a) {
    echo sprintf("%-4d | %-25s | %-15s | %-12s | %-10s | %-12s | %-15s | %-15s\n",
        $a->id,
        $a->label,
        $a->annotation_type,
        $a->parent_annotation_id ?? 'null',
        $a->room_id ?? 'null',
        $a->room_location_id ?? 'null',
        $a->cabinet_run_id ?? 'null',
        $a->cabinet_specification_id ?? 'null'
    );
}

echo str_repeat('=', 150) . "\n";
echo "\nTotal: " . $annotations->count() . " annotations\n";

// Also show ALL annotations for this project
echo "\n\n🌍 ALL Annotations in Project (all pages):\n";
echo str_repeat('=', 150) . "\n";
echo sprintf("%-4s | %-8s | %-25s | %-15s | %-12s\n",
    "ID", "Page", "Label", "Type", "Parent ID");
echo str_repeat('=', 150) . "\n";

$allAnnotations = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function($q) {
    $q->where('pdf_document_id', function($subq) {
        $subq->select('pdf_document_id')
            ->from('pdf_pages')
            ->where('id', 2)
            ->limit(1);
    });
})->orderBy('pdf_page_id')->orderBy('id')->get();

foreach ($allAnnotations as $a) {
    echo sprintf("%-4d | %-8s | %-25s | %-15s | %-12s\n",
        $a->id,
        "Page " . $a->pdf_page_id,
        $a->label,
        $a->annotation_type,
        $a->parent_annotation_id ?? 'null'
    );
}

echo str_repeat('=', 150) . "\n";
echo "\nTotal in project: " . $allAnnotations->count() . " annotations\n\n";

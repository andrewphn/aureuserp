<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🏠 ROOMS for Project #9:\n";
echo str_repeat('=', 80) . "\n";
$rooms = \Webkul\Project\Models\Room::where('project_id', 9)->get();
foreach ($rooms as $room) {
    echo "ID: {$room->id} | Name: {$room->name}\n";
}

echo "\n\n📍 PDF PAGE ANNOTATIONS for Project #9:\n";
echo str_repeat('=', 120) . "\n";
echo sprintf("%-4s | %-10s | %-15s | %-25s | %-12s | %-12s\n",
    "ID", "Page", "Type", "Label", "Room ID", "Parent ID");
echo str_repeat('=', 120) . "\n";

$annotations = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function($q) {
    $q->whereHas('pdfDocument', function($subq) {
        $subq->where('project_id', 9);
    });
})->with('pdfPage')->orderBy('pdf_page_id')->orderBy('id')->get();

foreach ($annotations as $a) {
    echo sprintf("%-4d | %-10s | %-15s | %-25s | %-12s | %-12s\n",
        $a->id,
        "Page " . $a->pdf_page_id,
        $a->annotation_type,
        $a->label,
        $a->room_id ?? 'null',
        $a->parent_annotation_id ?? 'null'
    );
}

echo str_repeat('=', 120) . "\n";
echo "\nTotal annotations: " . $annotations->count() . "\n\n";

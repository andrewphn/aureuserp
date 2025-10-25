<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get PDF pages for project 9
$pdfPages = \App\Models\PdfPage::whereHas('pdfDocument.project', function($q) {
    $q->where('id', 9);
})->with('annotations')->orderBy('page_number')->get();

echo "\n📄 PDF Pages for Project #9 (25 Friendship Lane):\n";
echo str_repeat('=', 120) . "\n";

foreach ($pdfPages as $page) {
    echo "\n📑 Page {$page->page_number} (ID: {$page->id}):\n";

    if ($page->annotations->isEmpty()) {
        echo "   (No annotations)\n";
    } else {
        echo sprintf("   %-4s | %-30s | %-15s | %-12s | %-10s\n",
            "ID", "Label", "Type", "Parent ID", "Room ID");
        echo "   " . str_repeat('-', 110) . "\n";

        foreach ($page->annotations as $a) {
            echo sprintf("   %-4d | %-30s | %-15s | %-12s | %-10s\n",
                $a->id,
                $a->label,
                $a->annotation_type,
                $a->parent_annotation_id ?? 'null',
                $a->room_id ?? 'null'
            );
        }
    }
}

echo "\n" . str_repeat('=', 120) . "\n\n";

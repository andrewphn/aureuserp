<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$projects = \Webkul\Project\Models\Project::whereNotNull('pdf_document_id')->get();

echo "Projects with PDF documents:\n";
foreach ($projects as $project) {
    echo "- ID: {$project->id}, Name: {$project->name}, PDF Doc ID: {$project->pdf_document_id}\n";
}

if ($projects->isEmpty()) {
    echo "\nNo projects have PDF documents attached.\n";
}

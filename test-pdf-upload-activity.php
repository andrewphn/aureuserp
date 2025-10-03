<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PdfDocument;
use Webkul\Project\Models\Project;
use App\Models\User;

// Find the test project
$project = Project::find(1);
if (!$project) {
    echo "Project not found\n";
    exit(1);
}

// Find the user
$user = User::first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

// Create a test PDF document
$testPdfPath = 'test-google-drive-pdf.pdf';
$fullPath = storage_path('app/public/pdf-documents/' . basename($testPdfPath));

if (!file_exists($fullPath)) {
    echo "Test PDF file not found at: $fullPath\n";
    echo "Using placeholder data instead\n";
}

echo "Creating test PDF document...\n";
echo "Project: {$project->name} (ID: {$project->id})\n";
echo "User: {$user->name} (ID: {$user->id})\n";

$document = new PdfDocument([
    'module_type' => get_class($project),
    'module_id' => $project->id,
    'file_name' => 'Test Activity Logging.pdf',
    'file_path' => 'pdf-documents/test-activity-logging.pdf',
    'file_size' => 12345,
    'mime_type' => 'application/pdf',
    'page_count' => 1,
    'uploaded_by' => $user->id,
]);

$document->save();

echo "\n✓ PDF Document created successfully!\n";
echo "Document ID: {$document->id}\n";
echo "File Name: {$document->file_name}\n\n";

// Check if activity was logged
$activities = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($project))
    ->where('subject_id', $project->id)
    ->orderBy('created_at', 'desc')
    ->get();

echo "=== Project Activities ===\n";
foreach ($activities as $activity) {
    echo "[{$activity->created_at}] {$activity->description}\n";
    if ($activity->properties) {
        echo "  Properties: " . json_encode($activity->properties, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

if ($activities->count() === 0) {
    echo "⚠️  No activities found! Activity logging might not be working.\n";
} else {
    $hasUploadActivity = $activities->filter(function($activity) {
        return str_contains($activity->description, 'uploaded PDF');
    })->count() > 0;

    if ($hasUploadActivity) {
        echo "✓ SUCCESS! PDF upload activity was logged correctly!\n";
    } else {
        echo "⚠️  No 'uploaded PDF' activity found. Check PdfDocumentObserver.\n";
    }
}

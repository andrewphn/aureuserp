// Test PDF upload activity logging using tinker commands

// Login as first user
auth()->loginUsingId(1);

// Get project
$project = \Webkul\Project\Models\Project::find(1);

// Create test PDF document
$document = new \App\Models\PdfDocument([
    'module_type' => get_class($project),
    'module_id' => $project->id,
    'file_name' => 'Test Activity Logging.pdf',
    'file_path' => 'pdf-documents/test-activity-logging.pdf',
    'file_size' => 12345,
    'mime_type' => 'application/pdf',
    'page_count' => 1,
    'uploaded_by' => auth()->id(),
]);

$document->save();

echo "Document created: {$document->id}\n";

// Check activities
$activities = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($project))
    ->where('subject_id', $project->id)
    ->orderBy('created_at', 'desc')
    ->get();

echo "\n=== Project Activities ===\n";
foreach ($activities as $activity) {
    echo "[{$activity->created_at}] {$activity->description}\n";
}

$hasUploadActivity = $activities->filter(function($activity) {
    return str_contains($activity->description, 'uploaded PDF');
})->count() > 0;

if ($hasUploadActivity) {
    echo "\n✓ SUCCESS! PDF upload activity was logged!\n";
} else {
    echo "\n⚠️  No upload activity found\n";
}

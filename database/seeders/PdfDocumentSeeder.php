<?php

namespace Database\Seeders;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfAnnotation;
use App\Models\PdfDocumentActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pdf Document Seeder database seeder
 *
 */
class PdfDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and partners for realistic data
        $users = DB::table('users')->pluck('id')->toArray();
        $partners = DB::table('partners_partners')->limit(5)->pluck('id')->toArray();

        if (empty($users) || empty($partners)) {
            $this->command->warn('No users or partners found. Skipping PDF document seeding.');
            return;
        }

        $this->command->info('Seeding PDF documents...');

        // Sample PDF documents
        $documents = [
            [
                'module_type' => 'Partner',
                'module_id' => $partners[0],
                'file_name' => 'project_blueprint_001.pdf',
                'file_path' => 'pdfs/2025/09/project_blueprint_001.pdf',
                'file_size' => 2458624, // ~2.4MB
                'page_count' => 8,
                'tags' => ['blueprint', 'design', 'woodwork'],
                'metadata' => [
                    'original_name' => 'Kitchen Cabinet Design - Final.pdf',
                    'uploaded_via' => 'web',
                    'project_phase' => 'planning',
                ],
            ],
            [
                'module_type' => 'Partner',
                'module_id' => $partners[1] ?? $partners[0],
                'file_name' => 'material_specifications.pdf',
                'file_path' => 'pdfs/2025/09/material_specifications.pdf',
                'file_size' => 1245632, // ~1.2MB
                'page_count' => 4,
                'tags' => ['materials', 'specifications'],
                'metadata' => [
                    'original_name' => 'Material Specs - Oak.pdf',
                    'uploaded_via' => 'web',
                ],
            ],
            [
                'module_type' => 'Partner',
                'module_id' => $partners[2] ?? $partners[0],
                'file_name' => 'installation_guide.pdf',
                'file_path' => 'pdfs/2025/09/installation_guide.pdf',
                'file_size' => 3567890, // ~3.5MB
                'page_count' => 12,
                'tags' => ['installation', 'guide', 'manual'],
                'metadata' => [
                    'original_name' => 'Installation Manual v2.pdf',
                    'uploaded_via' => 'api',
                ],
            ],
            [
                'module_type' => 'Partner',
                'module_id' => $partners[3] ?? $partners[0],
                'file_name' => 'quotation_2025_001.pdf',
                'file_path' => 'pdfs/2025/09/quotation_2025_001.pdf',
                'file_size' => 876543, // ~876KB
                'page_count' => 3,
                'tags' => ['quotation', 'pricing'],
                'metadata' => [
                    'original_name' => 'Quote - Custom Shelving.pdf',
                    'uploaded_via' => 'web',
                    'quote_number' => 'Q-2025-001',
                ],
            ],
            [
                'module_type' => 'Partner',
                'module_id' => $partners[4] ?? $partners[0],
                'file_name' => 'technical_drawing.pdf',
                'file_path' => 'pdfs/2025/09/technical_drawing.pdf',
                'file_size' => 4567890, // ~4.5MB
                'page_count' => 15,
                'tags' => ['technical', 'drawing', 'cad'],
                'metadata' => [
                    'original_name' => 'CAD Export - Wall Unit.pdf',
                    'uploaded_via' => 'web',
                ],
            ],
        ];

        // Create documents with pages
        foreach ($documents as $docData) {
            $uploadedBy = $users[array_rand($users)];

            $document = PdfDocument::create([
                'module_type' => $docData['module_type'],
                'module_id' => $docData['module_id'],
                'file_name' => $docData['file_name'],
                'file_path' => $docData['file_path'],
                'file_size' => $docData['file_size'],
                'mime_type' => 'application/pdf',
                'page_count' => $docData['page_count'],
                'uploaded_by' => $uploadedBy,
                'tags' => $docData['tags'],
                'metadata' => $docData['metadata'],
            ]);

            // Create pages for this document
            for ($i = 1; $i <= $docData['page_count']; $i++) {
                PdfPage::create([
                    'document_id' => $document->id,
                    'page_number' => $i,
                    'thumbnail_path' => "pdfs/thumbnails/{$document->id}/page_{$i}.jpg",
                    'extracted_text' => $this->generateSampleText($i),
                    'page_metadata' => [
                        'width' => 8.5,
                        'height' => 11,
                        'rotation' => 0,
                        'has_images' => rand(0, 1) === 1,
                    ],
                ]);
            }

            // Log upload activity
            PdfDocumentActivity::log(
                $document->id,
                $uploadedBy,
                PdfDocumentActivity::ACTION_UPLOADED,
                ['source' => $docData['metadata']['uploaded_via'] ?? 'web']
            );

            $this->command->info("Created document: {$document->file_name} ({$document->page_count} pages)");
        }

        // Create sample annotations
        $this->command->info('Creating sample annotations...');

        $allDocuments = PdfDocument::with('pages')->get();
        $annotationCount = 0;

        foreach ($allDocuments as $doc) {
            // Add 2-4 annotations per document
            $numAnnotations = rand(2, 4);

            for ($i = 0; $i < $numAnnotations; $i++) {
                $authorId = $users[array_rand($users)];
                $author = DB::table('users')->where('id', $authorId)->first();
                $page = $doc->pages->random();

                $annotationType = [
                    PdfAnnotation::TYPE_HIGHLIGHT,
                    PdfAnnotation::TYPE_TEXT,
                    PdfAnnotation::TYPE_DRAWING,
                    PdfAnnotation::TYPE_ARROW,
                ][array_rand([0, 1, 2, 3])];

                $annotationData = $this->generateAnnotationData($annotationType);

                PdfAnnotation::create([
                    'document_id' => $doc->id,
                    'page_number' => $page->page_number,
                    'annotation_type' => $annotationType,
                    'annotation_data' => $annotationData,
                    'author_id' => $authorId,
                    'author_name' => $author->name,
                ]);

                $annotationCount++;

                // Log annotation activity
                PdfDocumentActivity::log(
                    $doc->id,
                    $authorId,
                    PdfDocumentActivity::ACTION_ANNOTATED,
                    [
                        'annotation_type' => $annotationType,
                        'page_number' => $page->page_number,
                    ]
                );
            }
        }

        $this->command->info("Created {$annotationCount} annotations");

        // Create additional sample activities (views, downloads)
        $this->command->info('Creating sample activities...');

        foreach ($allDocuments as $doc) {
            // Add 3-5 view activities
            for ($i = 0; $i < rand(3, 5); $i++) {
                PdfDocumentActivity::log(
                    $doc->id,
                    $users[array_rand($users)],
                    PdfDocumentActivity::ACTION_VIEWED,
                    ['page_count' => rand(1, $doc->page_count)]
                );
            }

            // Add 1-2 download activities
            for ($i = 0; $i < rand(1, 2); $i++) {
                PdfDocumentActivity::log(
                    $doc->id,
                    $users[array_rand($users)],
                    PdfDocumentActivity::ACTION_DOWNLOADED
                );
            }
        }

        $this->command->info('PDF document seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Documents: ' . PdfDocument::count());
        $this->command->info('- Pages: ' . PdfPage::count());
        $this->command->info('- Annotations: ' . PdfAnnotation::count());
        $this->command->info('- Activities: ' . PdfDocumentActivity::count());
    }

    /**
     * Generate sample extracted text for a page.
     *
     * @param int $pageNumber
     * @return string
     */
    private function generateSampleText(int $pageNumber): string
    {
        $texts = [
            "This is a technical specification document for woodworking project. Page {$pageNumber} contains detailed measurements and material requirements.",
            "Design specifications: Oak wood, 3/4 inch thickness, finished with natural stain. Quality grade A. Page {$pageNumber}.",
            "Installation instructions: Step-by-step guide for proper assembly and installation. Refer to diagram on page {$pageNumber}.",
            "Material list: Premium hardwood, stainless steel hardware, wood glue, finishing materials. Page {$pageNumber}.",
            "Safety requirements: Always wear protective equipment. Follow OSHA guidelines. Detailed on page {$pageNumber}.",
        ];

        return $texts[array_rand($texts)];
    }

    /**
     * Generate sample annotation data based on type.
     *
     * @param string $type
     * @return array
     */
    private function generateAnnotationData(string $type): array
    {
        switch ($type) {
            case PdfAnnotation::TYPE_HIGHLIGHT:
                return [
                    'color' => ['#ffeb3b', '#4caf50', '#2196f3', '#f44336'][array_rand([0, 1, 2, 3])],
                    'position' => [
                        'x' => rand(50, 500),
                        'y' => rand(50, 700),
                        'width' => rand(100, 300),
                        'height' => 20,
                    ],
                    'text' => 'Important specification',
                ];

            case PdfAnnotation::TYPE_TEXT:
                $comments = [
                    'Please verify these measurements',
                    'Update material specs',
                    'Check with client',
                    'Great design!',
                    'Need clarification here',
                ];
                return [
                    'color' => '#ffc107',
                    'position' => [
                        'x' => rand(50, 500),
                        'y' => rand(50, 700),
                    ],
                    'text' => $comments[array_rand($comments)],
                ];

            case PdfAnnotation::TYPE_DRAWING:
                return [
                    'color' => '#f44336',
                    'stroke_width' => 2,
                    'points' => [
                        ['x' => rand(100, 200), 'y' => rand(100, 200)],
                        ['x' => rand(200, 300), 'y' => rand(200, 300)],
                        ['x' => rand(300, 400), 'y' => rand(300, 400)],
                    ],
                ];

            case PdfAnnotation::TYPE_ARROW:
                return [
                    'color' => '#2196f3',
                    'start' => ['x' => rand(100, 300), 'y' => rand(100, 300)],
                    'end' => ['x' => rand(300, 500), 'y' => rand(300, 500)],
                    'stroke_width' => 2,
                ];

            default:
                return [];
        }
    }
}

<?php

namespace Tests\Feature\GoogleDrive;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\CncFileIngestionService;

class CncFileIngestionTest extends TestCase
{
    protected CncFileIngestionService $ingestionService;

    protected ?Project $testProject = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestionService = new CncFileIngestionService();
    }

    protected function tearDown(): void
    {
        // Cleanup test project and CNC programs
        if ($this->testProject) {
            CncProgramPart::whereHas('cncProgram', function ($query) {
                $query->where('project_id', $this->testProject->id);
            })->forceDelete();

            CncProgram::where('project_id', $this->testProject->id)->forceDelete();
            $this->testProject->forceDelete();
            $this->testProject = null;
        }

        parent::tearDown();
    }

    /**
     * Test that VCarve files are detected and create CNC programs
     */
    public function test_vcarve_file_creates_cnc_program(): void
    {
        $this->testProject = Project::create([
            'name' => 'CNC Ingestion Test',
            'project_number' => 'TEST-CNC-' . time(),
            'company_id' => 1,
        ]);

        $mockFiles = [
            [
                'id' => 'drive-file-001',
                'name' => 'Kitchen_FL_Sheet1.crv',
                'path' => '04_Production/CNC/VCarve Files/Kitchen_FL_Sheet1.crv',
                'mimeType' => 'application/octet-stream',
                'isFolder' => false,
                'webViewLink' => 'https://drive.google.com/file/d/abc123',
            ],
        ];

        $results = $this->ingestionService->processChanges($this->testProject, [
            'added' => $mockFiles,
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(1, $results['programs_created'], 'Programs created. Results: ' . json_encode($results));

        $program = CncProgram::where('project_id', $this->testProject->id)->first();
        $this->assertNotNull($program);
        $this->assertEquals('Kitchen_FL_Sheet1', $program->name);
        $this->assertEquals('FL', $program->material_code);
    }

    /**
     * Test that G-code files create program parts
     */
    public function test_gcode_file_creates_program_part(): void
    {
        $this->testProject = Project::create([
            'name' => 'CNC Parts Test',
            'project_number' => 'TEST-CNC-PARTS-' . time(),
            'company_id' => 1,
        ]);

        // Create a program first - note the service will match by name pattern
        $program = CncProgram::create([
            'project_id' => $this->testProject->id,
            'name' => 'TestProgram',
            'status' => CncProgram::STATUS_PENDING,
            'creator_id' => 1,
        ]);

        // Ensure the program is saved and queryable
        $this->assertDatabaseHas('projects_cnc_programs', [
            'id' => $program->id,
            'name' => 'TestProgram',
        ]);

        $mockFiles = [
            [
                'id' => 'drive-file-002',
                'name' => 'TestProgram_Sheet1.nc',
                'path' => '04_Production/CNC/ToolPaths/TestProgram_Sheet1.nc',
                'mimeType' => 'application/octet-stream',
                'isFolder' => false,
                'webViewLink' => 'https://drive.google.com/file/d/def456',
                'size' => 12345,
            ],
        ];

        $results = $this->ingestionService->processChanges($this->testProject, [
            'added' => $mockFiles,
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(1, $results['parts_created'], 'Expected 1 part to be created. Results: ' . json_encode($results));

        $part = CncProgramPart::where('cnc_program_id', $program->id)->first();
        $this->assertNotNull($part);
        $this->assertEquals('TestProgram_Sheet1', $part->file_name);
        $this->assertEquals(1, $part->sheet_number);
    }

    /**
     * Test that files outside CNC folder are ignored
     */
    public function test_files_outside_cnc_folder_are_ignored(): void
    {
        $this->testProject = Project::create([
            'name' => 'CNC Ignore Test',
            'project_number' => 'TEST-CNC-IGNORE-' . time(),
            'company_id' => 1,
        ]);

        $mockFiles = [
            'file1' => [
                'id' => 'drive-file-003',
                'name' => 'SomeDocument.crv',
                'path' => '01_Discovery/Proposal/SomeDocument.crv',
                'mimeType' => 'application/octet-stream',
                'isFolder' => false,
            ],
        ];

        $results = $this->ingestionService->processChanges($this->testProject, [
            'added' => array_values($mockFiles),
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(0, $results['programs_created']);
        $this->assertEquals(0, $results['parts_created']);
    }

    /**
     * Test material code extraction from filenames
     */
    public function test_material_code_extraction(): void
    {
        $this->testProject = Project::create([
            'name' => 'Material Code Test',
            'project_number' => 'TEST-MATERIAL-' . time(),
            'company_id' => 1,
        ]);

        $mockFiles = [
            [
                'id' => 'file-fl',
                'name' => 'Project_FL_Parts.crv',
                'path' => '04_Production/CNC/VCarve Files/Project_FL_Parts.crv',
                'isFolder' => false,
            ],
            [
                'id' => 'file-rift',
                'name' => 'Cabinet_RiftWOPly.crv',
                'path' => '04_Production/CNC/VCarve Files/Cabinet_RiftWOPly.crv',
                'isFolder' => false,
            ],
            [
                'id' => 'file-medex',
                'name' => 'Drawer_Medex_Sides.crv',
                'path' => '04_Production/CNC/VCarve Files/Drawer_Medex_Sides.crv',
                'isFolder' => false,
            ],
        ];

        $results = $this->ingestionService->processChanges($this->testProject, [
            'added' => $mockFiles,
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(3, $results['programs_created']);

        $programs = CncProgram::where('project_id', $this->testProject->id)
            ->orderBy('name')
            ->get();

        $this->assertEquals('FL', $programs->where('name', 'Project_FL_Parts')->first()->material_code);
        $this->assertEquals('RiftWOPly', $programs->where('name', 'Cabinet_RiftWOPly')->first()->material_code);
        $this->assertEquals('Medex', $programs->where('name', 'Drawer_Medex_Sides')->first()->material_code);
    }

    /**
     * Test that duplicate files update existing programs
     */
    public function test_duplicate_files_update_existing_programs(): void
    {
        $this->testProject = Project::create([
            'name' => 'Duplicate Test',
            'project_number' => 'TEST-DUP-' . time(),
            'company_id' => 1,
        ]);

        $mockFile = [
            'id' => 'drive-file-dup',
            'name' => 'DuplicateTest.crv',
            'path' => '04_Production/CNC/VCarve Files/DuplicateTest.crv',
            'isFolder' => false,
        ];

        // First ingestion
        $results1 = $this->ingestionService->processChanges($this->testProject, [
            'added' => [$mockFile],
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(1, $results1['programs_created']);
        $this->assertEquals(0, $results1['programs_updated']);

        // Second ingestion (same file modified)
        $results2 = $this->ingestionService->processChanges($this->testProject, [
            'added' => [],
            'modified' => [$mockFile],
            'deleted' => [],
        ]);

        $this->assertEquals(0, $results2['programs_created']);
        $this->assertEquals(1, $results2['programs_updated']);

        // Should still only have 1 program
        $programCount = CncProgram::where('project_id', $this->testProject->id)->count();
        $this->assertEquals(1, $programCount);
    }

    /**
     * Test full scan of mock file list
     */
    public function test_full_scan_processes_all_cnc_files(): void
    {
        $this->testProject = Project::create([
            'name' => 'Full Scan Test',
            'project_number' => 'TEST-SCAN-' . time(),
            'company_id' => 1,
        ]);

        $mockFiles = [
            'folder1' => [
                'id' => 'folder-production',
                'name' => '04_Production',
                'path' => '04_Production',
                'isFolder' => true,
            ],
            'folder2' => [
                'id' => 'folder-cnc',
                'name' => 'CNC',
                'path' => '04_Production/CNC',
                'isFolder' => true,
            ],
            'folder3' => [
                'id' => 'folder-vcarve',
                'name' => 'VCarve Files',
                'path' => '04_Production/CNC/VCarve Files',
                'isFolder' => true,
            ],
            'file1' => [
                'id' => 'file-001',
                'name' => 'Cabinet_FL.crv',
                'path' => '04_Production/CNC/VCarve Files/Cabinet_FL.crv',
                'isFolder' => false,
            ],
            'file2' => [
                'id' => 'file-002',
                'name' => 'Drawer_Medex.crv3d',
                'path' => '04_Production/CNC/VCarve Files/Drawer_Medex.crv3d',
                'isFolder' => false,
            ],
            'file3' => [
                'id' => 'file-003',
                'name' => 'Cabinet_FL_Sheet1.nc',
                'path' => '04_Production/CNC/ToolPaths/Cabinet_FL_Sheet1.nc',
                'isFolder' => false,
            ],
            'file4' => [
                'id' => 'file-004',
                'name' => 'RandomDoc.pdf',
                'path' => '01_Discovery/RandomDoc.pdf',
                'isFolder' => false,
            ],
        ];

        $results = $this->ingestionService->fullScan($this->testProject, $mockFiles);

        // Should create 2 programs (2 VCarve files) and 1 part (1 NC file)
        $this->assertEquals(2, $results['programs_created']);
        $this->assertEquals(1, $results['parts_created']);
    }

    /**
     * Test sheet number extraction from G-code filenames
     */
    public function test_sheet_number_extraction(): void
    {
        $this->testProject = Project::create([
            'name' => 'Sheet Number Test',
            'project_number' => 'TEST-SHEET-' . time(),
            'company_id' => 1,
        ]);

        // Create a program
        $program = CncProgram::create([
            'project_id' => $this->testProject->id,
            'name' => 'SheetTest',
            'status' => CncProgram::STATUS_PENDING,
            'creator_id' => 1,
        ]);

        $mockFiles = [
            [
                'id' => 'sheet-1',
                'name' => 'SheetTest_Sheet1.nc',
                'path' => '04_Production/CNC/ToolPaths/SheetTest_Sheet1.nc',
                'isFolder' => false,
            ],
            [
                'id' => 'sheet-2',
                'name' => 'SheetTest_Sheet2.nc',
                'path' => '04_Production/CNC/ToolPaths/SheetTest_Sheet2.nc',
                'isFolder' => false,
            ],
            [
                'id' => 'sheet-3',
                'name' => 'SheetTest_S3.nc',
                'path' => '04_Production/CNC/ToolPaths/SheetTest_S3.nc',
                'isFolder' => false,
            ],
        ];

        $results = $this->ingestionService->processChanges($this->testProject, [
            'added' => $mockFiles,
            'modified' => [],
            'deleted' => [],
        ]);

        $this->assertEquals(3, $results['parts_created']);

        $parts = CncProgramPart::where('cnc_program_id', $program->id)
            ->orderBy('sheet_number')
            ->get();

        $this->assertEquals(1, $parts[0]->sheet_number);
        $this->assertEquals(2, $parts[1]->sheet_number);
        $this->assertEquals(3, $parts[2]->sheet_number);
    }
}

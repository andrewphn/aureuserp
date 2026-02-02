<?php

namespace Webkul\Project\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\FileList;
use Webkul\Project\Services\GoogleDrive\GoogleDriveAuthService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveFolderService;

class GoogleDriveFolderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_navigate_to_subfolder_returns_root_when_path_empty(): void
    {
        $mockAuthService = Mockery::mock(GoogleDriveAuthService::class);
        $mockAuthService->shouldReceive('getDriveService')->andReturn(null);

        $service = new GoogleDriveFolderService($mockAuthService);

        $result = $service->navigateToSubfolder('root-folder-id', '');

        $this->assertEquals('root-folder-id', $result);
    }

    public function test_navigate_to_subfolder_returns_null_when_folder_not_found(): void
    {
        $mockDriveService = Mockery::mock(Drive::class);

        // Mock files resource
        $mockFiles = Mockery::mock();
        $mockFileList = Mockery::mock(FileList::class);
        $mockFileList->shouldReceive('getFiles')->andReturn([]);

        $mockFiles->shouldReceive('listFiles')->andReturn($mockFileList);
        $mockDriveService->files = $mockFiles;

        $mockAuthService = Mockery::mock(GoogleDriveAuthService::class);
        $mockAuthService->shouldReceive('getDriveService')->andReturn($mockDriveService);

        $service = new GoogleDriveFolderService($mockAuthService);

        $result = $service->navigateToSubfolder('root-folder-id', '02_Design/NonExistent');

        $this->assertNull($result);
    }

    public function test_find_files_by_extension_filters_correctly(): void
    {
        // Create mock file objects
        $mockRhinoFile = Mockery::mock(DriveFile::class);
        $mockRhinoFile->shouldReceive('getId')->andReturn('file-1');
        $mockRhinoFile->shouldReceive('getName')->andReturn('design.3dm');
        $mockRhinoFile->shouldReceive('getMimeType')->andReturn('application/octet-stream');
        $mockRhinoFile->shouldReceive('getSize')->andReturn(1024);
        $mockRhinoFile->shouldReceive('getWebViewLink')->andReturn('https://drive.google.com/file/1');
        $mockRhinoFile->shouldReceive('getThumbnailLink')->andReturn(null);
        $mockRhinoFile->shouldReceive('getCreatedTime')->andReturn('2024-01-01');
        $mockRhinoFile->shouldReceive('getModifiedTime')->andReturn('2024-01-02');

        $mockPdfFile = Mockery::mock(DriveFile::class);
        $mockPdfFile->shouldReceive('getId')->andReturn('file-2');
        $mockPdfFile->shouldReceive('getName')->andReturn('document.pdf');
        $mockPdfFile->shouldReceive('getMimeType')->andReturn('application/pdf');
        $mockPdfFile->shouldReceive('getSize')->andReturn(2048);
        $mockPdfFile->shouldReceive('getWebViewLink')->andReturn('https://drive.google.com/file/2');
        $mockPdfFile->shouldReceive('getThumbnailLink')->andReturn(null);
        $mockPdfFile->shouldReceive('getCreatedTime')->andReturn('2024-01-01');
        $mockPdfFile->shouldReceive('getModifiedTime')->andReturn('2024-01-02');

        $mockDriveService = Mockery::mock(Drive::class);
        $mockFiles = Mockery::mock();
        $mockFileList = Mockery::mock(FileList::class);
        $mockFileList->shouldReceive('getFiles')->andReturn([$mockRhinoFile, $mockPdfFile]);
        $mockFiles->shouldReceive('listFiles')->andReturn($mockFileList);
        $mockDriveService->files = $mockFiles;

        $mockAuthService = Mockery::mock(GoogleDriveAuthService::class);
        $mockAuthService->shouldReceive('getDriveService')->andReturn($mockDriveService);

        $service = new GoogleDriveFolderService($mockAuthService);

        // Test finding only .3dm files
        $result = $service->findFilesByExtension('folder-id', '3dm');

        $this->assertCount(1, $result);
        $this->assertEquals('design.3dm', $result[0]['name']);
    }

    public function test_find_files_by_multiple_extensions(): void
    {
        $mockFiles = [];
        $fileData = [
            ['id' => '1', 'name' => 'model.3dm', 'mime' => 'application/octet-stream'],
            ['id' => '2', 'name' => 'drawing.dwg', 'mime' => 'application/octet-stream'],
            ['id' => '3', 'name' => 'notes.txt', 'mime' => 'text/plain'],
        ];

        foreach ($fileData as $data) {
            $mockFile = Mockery::mock(DriveFile::class);
            $mockFile->shouldReceive('getId')->andReturn($data['id']);
            $mockFile->shouldReceive('getName')->andReturn($data['name']);
            $mockFile->shouldReceive('getMimeType')->andReturn($data['mime']);
            $mockFile->shouldReceive('getSize')->andReturn(1024);
            $mockFile->shouldReceive('getWebViewLink')->andReturn("https://drive.google.com/file/{$data['id']}");
            $mockFile->shouldReceive('getThumbnailLink')->andReturn(null);
            $mockFile->shouldReceive('getCreatedTime')->andReturn('2024-01-01');
            $mockFile->shouldReceive('getModifiedTime')->andReturn('2024-01-02');
            $mockFiles[] = $mockFile;
        }

        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockFileList = Mockery::mock(FileList::class);
        $mockFileList->shouldReceive('getFiles')->andReturn($mockFiles);
        $mockFilesResource->shouldReceive('listFiles')->andReturn($mockFileList);
        $mockDriveService->files = $mockFilesResource;

        $mockAuthService = Mockery::mock(GoogleDriveAuthService::class);
        $mockAuthService->shouldReceive('getDriveService')->andReturn($mockDriveService);

        $service = new GoogleDriveFolderService($mockAuthService);

        // Test finding .3dm and .dwg files
        $result = $service->findFilesByExtension('folder-id', ['3dm', 'dwg']);

        $this->assertCount(2, $result);
    }

    public function test_check_project_has_design_files_returns_correct_structure(): void
    {
        $mockDriveService = Mockery::mock(Drive::class);

        // Mock folder navigation - return empty for subfolder search (folder not found)
        $mockFiles = Mockery::mock();
        $mockFileList = Mockery::mock(FileList::class);
        $mockFileList->shouldReceive('getFiles')->andReturn([]);
        $mockFiles->shouldReceive('listFiles')->andReturn($mockFileList);
        $mockDriveService->files = $mockFiles;

        $mockAuthService = Mockery::mock(GoogleDriveAuthService::class);
        $mockAuthService->shouldReceive('getDriveService')->andReturn($mockDriveService);

        $service = new GoogleDriveFolderService($mockAuthService);

        $result = $service->checkProjectHasDesignFiles(
            'root-folder-id',
            '02_Design/DWG_Imports',
            ['3dm', 'dwg']
        );

        $this->assertArrayHasKey('exists', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('files', $result);
        $this->assertArrayHasKey('folder_found', $result);
        $this->assertFalse($result['exists']);
        $this->assertFalse($result['folder_found']);
    }

    public function test_folder_structure_constant_has_expected_paths(): void
    {
        $structure = GoogleDriveFolderService::FOLDER_STRUCTURE;

        // Verify expected top-level folders exist
        $this->assertArrayHasKey('01_Discovery', $structure);
        $this->assertArrayHasKey('02_Design', $structure);
        $this->assertArrayHasKey('03_Sourcing', $structure);
        $this->assertArrayHasKey('04_Production', $structure);
        $this->assertArrayHasKey('05_Delivery', $structure);

        // Verify Design subfolders
        $this->assertContains('DWG_Imports', $structure['02_Design']);

        // Verify Production has CNC folder
        $this->assertArrayHasKey('CNC', $structure['04_Production']);

        // CNC subfolders are in an array (names may use underscores or spaces)
        $cncFolders = $structure['04_Production']['CNC'];
        $this->assertIsArray($cncFolders);
        $this->assertCount(3, $cncFolders); // VCarve, ToolPaths, Reference Photos

        // Verify Job_Cards folder exists
        $this->assertArrayHasKey('Job_Cards', $structure['04_Production']);
    }
}

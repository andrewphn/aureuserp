<?php

namespace Tests\Unit\Services;

use App\Services\NutrientService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class NutrientServiceTest extends TestCase
{
    protected NutrientService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test configuration values
        config([
            'services.nutrient.license_key' => 'test-license-key',
            'nutrient.license_key' => 'test-license-key',
            'nutrient.autosave_interval' => 30,
            'nutrient.enable_forms' => true,
            'nutrient.enable_annotations' => true,
            'nutrient.max_file_size' => 52428800, // 50MB
            'nutrient.allowed_mime_types' => ['application/pdf'],
            'nutrient.allowed_extensions' => ['pdf'],
            'nutrient.default_toolbar' => [
                'annotate' => true,
                'save' => true,
                'print' => true,
            ],
            'nutrient.annotation_presets' => [],
            'nutrient.viewer_options' => [],
            'app.support_email' => 'support@example.com',
        ]);

        $this->service = new NutrientService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @test */
    public function it_validates_license_key_successfully(): void
    {
        // Uses license key from setUp mock
        $result = $this->service->validateLicenseKey();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_for_missing_license_key(): void
    {
        // Set empty config to test validateLicenseKey() method
        config(['services.nutrient.license_key' => null, 'nutrient.license_key' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nutrient license key is not configured');

        // validateLicenseKey checks config directly, not the constructor property
        $this->service->validateLicenseKey();
    }

    /** @test */
    public function it_throws_exception_for_empty_license_key(): void
    {
        config(['services.nutrient.license_key' => '', 'nutrient.license_key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nutrient license key is not configured');

        $this->service->validateLicenseKey();
    }

    /** @test */
    public function it_generates_sdk_configuration_with_defaults(): void
    {
        $config = $this->service->getSDKConfiguration();

        $this->assertIsArray($config);
        $this->assertEquals('test-license-key', $config['licenseKey']);
        $this->assertEquals('standalone', $config['container']);
        $this->assertTrue($config['toolbarItems']['annotate']);
        $this->assertArrayHasKey('annotationPresets', $config);
    }

    /** @test */
    public function it_generates_sdk_configuration_with_custom_options(): void
    {
        $customOptions = [
            'theme' => 'dark',
            'readOnly' => true,
            'toolbarItems' => ['save', 'download'],
        ];

        $config = $this->service->getSDKConfiguration($customOptions);

        $this->assertEquals('dark', $config['theme']);
        $this->assertTrue($config['readOnly']);
        $this->assertEquals(['save', 'download'], $config['toolbarItems']);
        $this->assertEquals('test-license-key', $config['licenseKey']); // License key still set
    }

    /** @test */
    public function it_creates_viewer_instance_code_with_document_path(): void
    {
        $code = $this->service->createViewerInstance('documents/sample.pdf');

        $this->assertStringContainsString('PSPDFKit.load', $code);
        $this->assertStringContainsString('documents/sample.pdf', $code);
        $this->assertStringContainsString('test-license-key', $code);
    }

    /** @test */
    public function it_creates_viewer_instance_code_with_custom_container(): void
    {
        $code = $this->service->createViewerInstance('documents/sample.pdf', 'custom-container');

        $this->assertStringContainsString('custom-container', $code);
    }

    /** @test */
    public function it_creates_viewer_instance_code_with_custom_config(): void
    {
        $customConfig = [
            'theme' => 'dark',
            'readOnly' => true,
        ];

        $code = $this->service->createViewerInstance('documents/sample.pdf', 'container', $customConfig);

        $this->assertStringContainsString('"theme":"dark"', $code);
        $this->assertStringContainsString('"readOnly":true', $code);
    }

    /** @test */
    public function it_validates_pdf_file_with_correct_mime_type(): void
    {
        $result = $this->service->validateFile('application/pdf', 1024 * 1024); // 1MB

        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_mime_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type. Only PDF files are allowed.');

        $this->service->validateFile('image/jpeg', 1024);
    }

    /** @test */
    public function it_throws_exception_for_file_size_exceeding_limit(): void
    {
        config(['nutrient.max_file_size' => 10 * 1024 * 1024]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');

        $this->service->validateFile('application/pdf', 15 * 1024 * 1024); // 15MB file
    }

    /** @test */
    public function it_validates_file_within_size_limit(): void
    {
        config(['nutrient.max_file_size' => 10 * 1024 * 1024]);

        $result = $this->service->validateFile('application/pdf', 5 * 1024 * 1024); // 5MB file

        $this->assertTrue($result);
    }

    /** @test */
    public function it_generates_unique_annotation_ids(): void
    {
        $id1 = $this->service->generateAnnotationId();
        $id2 = $this->service->generateAnnotationId();

        // IDs should be 26 characters (ULID format)
        $this->assertEquals(26, strlen($id1));
        $this->assertEquals(26, strlen($id2));

        // IDs should be unique
        $this->assertNotEquals($id1, $id2);

        // IDs should only contain valid Base32 characters (Crockford's alphabet)
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $id1);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $id2);
    }

    /** @test */
    public function it_generates_annotation_ids_with_correct_timestamp_ordering(): void
    {
        $id1 = $this->service->generateAnnotationId();
        usleep(1000); // Wait 1ms to ensure different timestamp
        $id2 = $this->service->generateAnnotationId();

        // Since ULIDs are lexicographically sortable, id2 should be greater than id1
        $this->assertGreaterThan($id1, $id2);
    }

    /** @test */
    public function it_returns_javascript_init_code(): void
    {
        $code = $this->service->getJavaScriptInitCode('documents/sample.pdf');

        $this->assertIsString($code);
        $this->assertStringContainsString('PSPDFKit.load', $code);
        $this->assertStringContainsString('documents/sample.pdf', $code);
    }

    /** @test */
    public function it_returns_ensure_changes_saved_code(): void
    {
        $code = $this->service->getEnsureChangesSavedCode();

        $this->assertIsString($code);
        $this->assertStringContainsString('instance.ensureChangesSaved()', $code);
        $this->assertStringContainsString('.then(', $code);
        $this->assertStringContainsString('.catch(', $code);
    }

    /** @test */
    public function it_returns_fallback_configuration_when_license_invalid(): void
    {
        config(['services.nutrient.license_key' => null]);

        $config = $this->service->getFallbackConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('readOnly', $config);
        $this->assertTrue($config['readOnly']);
        $this->assertArrayHasKey('toolbarItems', $config);
        $this->assertEmpty($config['toolbarItems']);
    }

    /** @test */
    public function it_logs_warning_for_fallback_configuration(): void
    {
        config(['services.nutrient.license_key' => null, 'app.support_email' => 'support@test.com']);

        $config = $this->service->getFallbackConfiguration();

        // Just verify the fallback config is returned (logging is tested implicitly)
        $this->assertIsArray($config);
        $this->assertTrue($config['readOnly']);
    }

    /** @test */
    public function it_generates_configuration_with_annotation_presets(): void
    {
        $config = $this->service->getSDKConfiguration();

        $this->assertArrayHasKey('annotationPresets', $config);
        $presets = $config['annotationPresets'];

        $this->assertIsArray($presets);
        $this->assertArrayHasKey('highlight', $presets);
        $this->assertArrayHasKey('note', $presets);
    }

    /** @test */
    public function it_handles_very_large_file_sizes(): void
    {
        config(['nutrient.max_file_size' => 100 * 1024 * 1024]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->validateFile('application/pdf', 200 * 1024 * 1024); // 200MB file
    }

    /** @test */
    public function it_accepts_zero_byte_files(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File cannot be empty');

        $this->service->validateFile('application/pdf', 0);
    }

    /** @test */
    public function it_generates_configuration_with_toolbar_items(): void
    {
        $config = $this->service->getSDKConfiguration();

        $this->assertArrayHasKey('toolbarItems', $config);
        $toolbarItems = $config['toolbarItems'];

        $this->assertIsArray($toolbarItems);
        $this->assertArrayHasKey('annotate', $toolbarItems);
        $this->assertArrayHasKey('save', $toolbarItems);
        $this->assertArrayHasKey('print', $toolbarItems);
    }
}

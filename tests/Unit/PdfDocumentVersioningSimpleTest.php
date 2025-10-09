<?php

namespace Tests\Unit;

use App\Models\PdfDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Simple Unit Tests for Phase 5: PDF Document Versioning
 * Tests version model without complex dependencies
 */
class PdfDocumentVersioningSimpleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test: Version fields are fillable
     */
    public function test_version_fields_are_fillable()
    {
        $fillable = (new PdfDocument())->getFillable();

        $this->assertContains('version_number', $fillable);
        $this->assertContains('previous_version_id', $fillable);
        $this->assertContains('is_latest_version', $fillable);
        $this->assertContains('version_metadata', $fillable);
    }

    /**
     * Test: Version fields are cast correctly
     */
    public function test_version_fields_have_correct_casts()
    {
        $casts = (new PdfDocument())->getCasts();

        $this->assertEquals('array', $casts['version_metadata']);
        $this->assertEquals('boolean', $casts['is_latest_version']);
    }

    /**
     * Test: previousVersion relationship exists
     */
    public function test_previous_version_relationship_exists()
    {
        $pdf = new PdfDocument();

        $this->assertTrue(method_exists($pdf, 'previousVersion'));
    }

    /**
     * Test: nextVersions relationship exists
     */
    public function test_next_versions_relationship_exists()
    {
        $pdf = new PdfDocument();

        $this->assertTrue(method_exists($pdf, 'nextVersions'));
    }

    /**
     * Test: getAllVersions method exists
     */
    public function test_get_all_versions_method_exists()
    {
        $pdf = new PdfDocument();

        $this->assertTrue(method_exists($pdf, 'getAllVersions'));
    }

    /**
     * Test: Can instantiate PdfDocument with version fields
     */
    public function test_can_instantiate_with_version_fields()
    {
        $pdf = new PdfDocument([
            'version_number' => 2,
            'previous_version_id' => 1,
            'is_latest_version' => true,
            'version_metadata' => ['notes' => 'Test version'],
        ]);

        $this->assertEquals(2, $pdf->version_number);
        $this->assertEquals(1, $pdf->previous_version_id);
        $this->assertTrue($pdf->is_latest_version);
        $this->assertEquals(['notes' => 'Test version'], $pdf->version_metadata);
    }

    /**
     * Test: version_metadata casts to array
     */
    public function test_version_metadata_casts_to_array()
    {
        $pdf = new PdfDocument();
        $pdf->version_metadata = ['key' => 'value'];

        $this->assertIsArray($pdf->version_metadata);
        $this->assertEquals('value', $pdf->version_metadata['key']);
    }

    /**
     * Test: is_latest_version casts to boolean
     */
    public function test_is_latest_version_casts_to_boolean()
    {
        $pdf = new PdfDocument();
        $pdf->is_latest_version = 1;

        $this->assertIsBool($pdf->is_latest_version);
        $this->assertTrue($pdf->is_latest_version);

        $pdf->is_latest_version = 0;
        $this->assertFalse($pdf->is_latest_version);
    }
}

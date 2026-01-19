<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TcsMaterialService;
use App\Services\RhinoExportService;
use App\Services\RhinoDataExtractor;
use App\Services\RhinoMCPService;
use App\Services\CabinetXYZService;

/**
 * TCS Rhino Standards Test Suite
 *
 * Tests the bidirectional Rhino/ERP layer standard for V-Carve CNC nesting.
 *
 * Run with: php artisan test --filter=TcsRhinoStandardsTest
 */
class TcsRhinoStandardsTest extends TestCase
{
    protected TcsMaterialService $materialService;
    protected RhinoExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->materialService = new TcsMaterialService();
        $this->exportService = new RhinoExportService($this->materialService);
    }

    // ============================================================
    // TcsMaterialService Tests
    // ============================================================

    public function test_material_layers_are_defined(): void
    {
        $materials = $this->materialService->getAvailableMaterials();

        $this->assertContains('3-4_PreFin', $materials);
        $this->assertContains('3-4_Medex', $materials);
        $this->assertContains('3-4_RiftWO', $materials);
        $this->assertContains('1-2_Baltic', $materials);
        $this->assertContains('1-4_Plywood', $materials);
        $this->assertContains('5-4_Hardwood', $materials);
    }

    public function test_material_config_has_required_fields(): void
    {
        $config = $this->materialService->getMaterialConfig('3-4_PreFin');

        $this->assertNotNull($config);
        $this->assertArrayHasKey('thickness', $config);
        $this->assertArrayHasKey('color', $config);
        $this->assertArrayHasKey('description', $config);
        $this->assertEquals(0.75, $config['thickness']);
    }

    public function test_part_type_to_material_mapping(): void
    {
        // Cabinet box -> PreFin
        $part = ['part_type' => 'cabinet_box'];
        $this->assertEquals('3-4_PreFin', $this->materialService->getMaterialForPart($part));

        // Drawer box -> Baltic
        $part = ['part_type' => 'drawer_box'];
        $this->assertEquals('1-2_Baltic', $this->materialService->getMaterialForPart($part));

        // Face frame -> Hardwood
        $part = ['part_type' => 'face_frame'];
        $this->assertEquals('5-4_Hardwood', $this->materialService->getMaterialForPart($part));

        // Toe kick -> Medex
        $part = ['part_type' => 'toe_kick'];
        $this->assertEquals('3-4_Medex', $this->materialService->getMaterialForPart($part));

        // Drawer box bottom -> 1/4 Plywood
        $part = ['part_type' => 'drawer_box_bottom'];
        $this->assertEquals('1-4_Plywood', $this->materialService->getMaterialForPart($part));
    }

    public function test_generate_tcs_metadata(): void
    {
        $part = [
            'part_type' => 'cabinet_box',
            'part_name' => 'Left Side',
            'cut_dimensions' => [
                'width' => 17,
                'length' => 27.25,
                'thickness' => 0.75,
            ],
            'dado' => [
                'depth' => 0.25,
                'width' => 0.25,
                'height_from_bottom' => 0.5,
            ],
        ];

        $metadata = $this->materialService->generateTcsMetadata($part, 'SANK-B36-001', 'left_side');

        // Identity
        $this->assertEquals('SANK-B36-001-Left_Side', $metadata['TCS_PART_ID']);
        $this->assertEquals('SANK-B36-001', $metadata['TCS_CABINET_ID']);
        $this->assertEquals('SANK', $metadata['TCS_PROJECT_CODE']);
        $this->assertEquals('cabinet_box', $metadata['TCS_PART_TYPE']);
        $this->assertEquals('Left Side', $metadata['TCS_PART_NAME']);

        // Material
        $this->assertEquals('3-4_PreFin', $metadata['TCS_MATERIAL']);
        $this->assertEquals(0.75, $metadata['TCS_THICKNESS']);

        // CNC Data
        $this->assertEquals(17, $metadata['TCS_CUT_WIDTH']);
        $this->assertEquals(27.25, $metadata['TCS_CUT_LENGTH']);
        $this->assertEquals('vertical', $metadata['TCS_GRAIN']);

        // Processing
        $this->assertArrayHasKey('TCS_EDGEBAND', $metadata);
        $this->assertArrayHasKey('TCS_MACHINING', $metadata);
        $this->assertArrayHasKey('TCS_DADO', $metadata);
    }

    public function test_extract_project_code(): void
    {
        // ERP format: TCS-001-ProjectName -> TCS-001
        $this->assertEquals('TCS-001', $this->materialService->extractProjectCode('TCS-001-9AustinFarmRoad-BTH1-SW-B1'));
        $this->assertEquals('TCS-0554', $this->materialService->extractProjectCode('TCS-0554-15WSankaty'));

        // Simple format: first segment
        $this->assertEquals('SANK', $this->materialService->extractProjectCode('SANK-B36-001'));
        $this->assertEquals('FSHIP', $this->materialService->extractProjectCode('FSHIP-W3030-002'));
        $this->assertEquals('CAB', $this->materialService->extractProjectCode('CAB-001'));
    }

    public function test_build_cabinet_id(): void
    {
        // New format: buildCabinetId(projectNumber, cabinetNumber)
        $this->assertEquals('AUST-BTH1-B1-C1', $this->materialService->buildCabinetId('TCS-001-9AustinFarmRoad', 'BTH1-B1-C1'));
        $this->assertEquals('SANK-KIT-01', $this->materialService->buildCabinetId('TCS-0554-15WSankaty', 'KIT-01'));

        // Legacy format still works via buildCabinetIdLegacy
        $this->assertEquals('SANK-B36-001', $this->materialService->buildCabinetIdLegacy('SANK', 'B36', 1));
        $this->assertEquals('FSHIP-W3030-012', $this->materialService->buildCabinetIdLegacy('fship', 'w3030', 12));
    }

    public function test_get_short_project_code(): void
    {
        $this->assertEquals('AUST', $this->materialService->getShortProjectCode('TCS-001-9AustinFarmRoad'));
        $this->assertEquals('SANK', $this->materialService->getShortProjectCode('TCS-0554-15WSankaty'));
        $this->assertEquals('FRIE', $this->materialService->getShortProjectCode('TCS-0123-FriendshipLane'));
    }

    public function test_build_part_id(): void
    {
        $partId = $this->materialService->buildPartId('TCS-001-9AustinFarmRoad', 'BTH1-B1-C1', 'Left Side');
        $this->assertEquals('AUST-BTH1-B1-C1-Left_Side', $partId);
    }

    public function test_parse_tcs_material_layer(): void
    {
        $parsed = $this->materialService->parseMaterialLayer('3-4_Medex');

        $this->assertNotNull($parsed);
        $this->assertEquals(0.75, $parsed['thickness']);
        $this->assertEquals('Medex', $parsed['material']);
        $this->assertEquals('3-4_Medex', $parsed['layer']);
        $this->assertEquals('tcs', $parsed['format']);
    }

    public function test_parse_legacy_layer_names(): void
    {
        // Standard legacy formats
        $parsed = $this->materialService->parseLegacyLayerName('3/4 Medex');
        $this->assertEquals('3-4_Medex', $parsed['layer']);

        $parsed = $this->materialService->parseLegacyLayerName('3/4" Rift WO');
        $this->assertEquals('3-4_RiftWO', $parsed['layer']);

        $parsed = $this->materialService->parseLegacyLayerName('1/2 Baltic');
        $this->assertEquals('1-2_Baltic', $parsed['layer']);

        // Material name only
        $parsed = $this->materialService->parseLegacyLayerName('RiftWO');
        $this->assertEquals('3-4_RiftWO', $parsed['layer']);
    }

    public function test_is_valid_material_layer(): void
    {
        $this->assertTrue($this->materialService->isValidMaterialLayer('3-4_PreFin'));
        $this->assertTrue($this->materialService->isValidMaterialLayer('1-2_Baltic'));
        $this->assertFalse($this->materialService->isValidMaterialLayer('invalid'));
        $this->assertFalse($this->materialService->isValidMaterialLayer('3/4 Medex')); // Legacy format
    }

    public function test_tcs_layer_hierarchy(): void
    {
        $hierarchy = $this->materialService->getTcsLayerHierarchy();

        $this->assertEquals('TCS_Materials', $hierarchy['parent']);
        $this->assertNotEmpty($hierarchy['layers']);

        $layerNames = array_column($hierarchy['layers'], 'name');
        $this->assertContains('3-4_PreFin', $layerNames);
        $this->assertContains('1-2_Baltic', $layerNames);

        // Check full paths
        $fullPaths = array_column($hierarchy['layers'], 'full_path');
        $this->assertContains('TCS_Materials::3-4_PreFin', $fullPaths);
    }

    // ============================================================
    // RhinoExportService Tests
    // ============================================================

    public function test_export_includes_tcs_metadata(): void
    {
        $auditData = $this->getSampleAuditData();

        $rhinoData = $this->exportService->generateRhinoData($auditData, [
            'include_tcs_metadata' => true,
            'cabinet_id' => 'SANK-B36-001',
        ]);

        // Check cabinet ID is set
        $this->assertEquals('SANK-B36-001', $rhinoData['cabinet_id']);

        // Check TCS layers are included
        $this->assertArrayHasKey('tcs_layers', $rhinoData);
        $this->assertEquals('TCS_Materials', $rhinoData['tcs_layers']['parent']);

        // Check parts have TCS metadata
        $this->assertNotEmpty($rhinoData['parts']);
        $firstPart = array_values($rhinoData['parts'])[0];

        $this->assertArrayHasKey('tcs_layer', $firstPart);
        $this->assertArrayHasKey('tcs_metadata', $firstPart);
        $this->assertArrayHasKey('TCS_PART_ID', $firstPart['tcs_metadata']);
        $this->assertArrayHasKey('TCS_CABINET_ID', $firstPart['tcs_metadata']);
        $this->assertArrayHasKey('TCS_PROJECT_CODE', $firstPart['tcs_metadata']);
    }

    public function test_export_without_tcs_metadata(): void
    {
        $auditData = $this->getSampleAuditData();

        $rhinoData = $this->exportService->generateRhinoData($auditData, [
            'include_tcs_metadata' => false,
        ]);

        // TCS layers should not be included
        $this->assertArrayNotHasKey('tcs_layers', $rhinoData);

        // Parts should not have TCS metadata
        $firstPart = array_values($rhinoData['parts'])[0];
        $this->assertArrayNotHasKey('tcs_layer', $firstPart);
        $this->assertArrayNotHasKey('tcs_metadata', $firstPart);
    }

    public function test_python_script_includes_tcs_functions(): void
    {
        $auditData = $this->getSampleAuditData();

        $script = $this->exportService->generatePythonScript($auditData);

        // Check for TCS layer functions
        $this->assertStringContainsString('create_tcs_layer_hierarchy', $script);
        $this->assertStringContainsString('set_tcs_user_text', $script);
        $this->assertStringContainsString('assign_to_tcs_layer', $script);

        // Check for TCS metadata in DATA
        $this->assertStringContainsString('TCS_PART_ID', $script);
        $this->assertStringContainsString('TCS_MATERIAL', $script);
        $this->assertStringContainsString('tcs_layers', $script);
    }

    // ============================================================
    // RhinoDataExtractor Tests (Unit - no MCP connection)
    // ============================================================

    public function test_extract_tcs_metadata_from_user_text(): void
    {
        // Mock RhinoMCPService
        $mockMcp = $this->createMock(RhinoMCPService::class);
        $extractor = new RhinoDataExtractor($mockMcp, $this->materialService);

        $objectInfo = [
            'guid' => 'test-guid-123',
            'name' => 'Left Side',
            'user_text' => [
                'TCS_PART_ID' => 'SANK-B36-001-LeftSide',
                'TCS_CABINET_ID' => 'SANK-B36-001',
                'TCS_PROJECT_CODE' => 'SANK',
                'TCS_PART_TYPE' => 'cabinet_box',
                'TCS_MATERIAL' => '3-4_PreFin',
                'TCS_THICKNESS' => '0.75',
                'TCS_CUT_WIDTH' => '17',
                'TCS_CUT_LENGTH' => '27.25',
                'TCS_GRAIN' => 'vertical',
                'TCS_EDGEBAND' => 'F',
                'TCS_MACHINING' => 'shelf_pins,dado_back',
                'TCS_DADO' => '0.25 x 0.25 @ 0.5',
            ],
        ];

        $metadata = $extractor->extractTcsMetadata($objectInfo);

        $this->assertEquals('SANK-B36-001-LeftSide', $metadata['part_id']);
        $this->assertEquals('SANK-B36-001', $metadata['cabinet_id']);
        $this->assertEquals('SANK', $metadata['project_code']);
        $this->assertEquals('cabinet_box', $metadata['part_type']);
        $this->assertEquals('3-4_PreFin', $metadata['material']);
        $this->assertEquals(0.75, $metadata['thickness']);
        $this->assertEquals(17.0, $metadata['cut_dimensions']['width']);
        $this->assertEquals(27.25, $metadata['cut_dimensions']['length']);
        $this->assertEquals('vertical', $metadata['grain']);
        $this->assertEquals('F', $metadata['edgeband']);
        $this->assertEquals('shelf_pins,dado_back', $metadata['machining']);

        // Dado parsing
        $this->assertNotNull($metadata['dado']);
        $this->assertEquals(0.25, $metadata['dado']['depth']);
        $this->assertEquals(0.25, $metadata['dado']['width']);
        $this->assertEquals(0.5, $metadata['dado']['height_from_bottom']);
    }

    public function test_extract_project_code_from_cabinet_id(): void
    {
        $mockMcp = $this->createMock(RhinoMCPService::class);
        $extractor = new RhinoDataExtractor($mockMcp, $this->materialService);

        // Without explicit project code - should extract from cabinet ID
        $objectInfo = [
            'user_text' => [
                'TCS_CABINET_ID' => 'FSHIP-W3030-002',
                // No TCS_PROJECT_CODE
            ],
        ];

        $metadata = $extractor->extractTcsMetadata($objectInfo);

        $this->assertEquals('FSHIP', $metadata['project_code']);
    }

    public function test_parse_material_layer_via_extractor(): void
    {
        $mockMcp = $this->createMock(RhinoMCPService::class);
        $extractor = new RhinoDataExtractor($mockMcp, $this->materialService);

        // TCS format
        $result = $extractor->parseMaterialLayer('3-4_Medex');
        $this->assertEquals(0.75, $result['thickness']);
        $this->assertEquals('Medex', $result['material']);

        // Legacy format via normalization
        $result = $extractor->normalizeLegacyLayer('3/4 Medex');
        $this->assertEquals('3-4_Medex', $result['layer']);
    }

    public function test_detect_material_from_layer(): void
    {
        $mockMcp = $this->createMock(RhinoMCPService::class);
        $extractor = new RhinoDataExtractor($mockMcp, $this->materialService);

        // With TCS_Materials prefix
        $result = $extractor->detectMaterialFromLayer('TCS_Materials::3-4_PreFin');
        $this->assertEquals('3-4_PreFin', $result['layer']);
        $this->assertEquals(0.75, $result['thickness']);

        // Direct TCS format
        $result = $extractor->detectMaterialFromLayer('1-2_Baltic');
        $this->assertEquals('1-2_Baltic', $result['layer']);
        $this->assertEquals(0.5, $result['thickness']);

        // Legacy format
        $result = $extractor->detectMaterialFromLayer('3/4" Rift WO');
        $this->assertEquals('3-4_RiftWO', $result['layer']);
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    protected function getSampleAuditData(): array
    {
        return [
            'cabinet_id' => 'TEST-B36-001',
            'input_specs' => [
                'width' => 36,
                'height' => 32.75,
                'depth' => 21,
                'toe_kick_height' => 4,
            ],
            'positions_3d' => [
                'parts' => [
                    'left_side' => [
                        'part_name' => 'Left Side',
                        'part_type' => 'cabinet_box',
                        'position' => ['x' => 0, 'y' => 0, 'z' => 0],
                        'dimensions' => ['w' => 0.75, 'h' => 28.75, 'd' => 20.25],
                        'cut_dimensions' => [
                            'width' => 28.75,
                            'length' => 20.25,
                            'thickness' => 0.75,
                        ],
                    ],
                    'right_side' => [
                        'part_name' => 'Right Side',
                        'part_type' => 'cabinet_box',
                        'position' => ['x' => 35.25, 'y' => 0, 'z' => 0],
                        'dimensions' => ['w' => 0.75, 'h' => 28.75, 'd' => 20.25],
                        'cut_dimensions' => [
                            'width' => 28.75,
                            'length' => 20.25,
                            'thickness' => 0.75,
                        ],
                    ],
                    'bottom' => [
                        'part_name' => 'Bottom Panel',
                        'part_type' => 'cabinet_box',
                        'position' => ['x' => 0.75, 'y' => 0, 'z' => 0],
                        'dimensions' => ['w' => 34.5, 'h' => 0.75, 'd' => 20.25],
                        'cut_dimensions' => [
                            'width' => 34.5,
                            'length' => 20.25,
                            'thickness' => 0.75,
                        ],
                    ],
                    'toe_kick' => [
                        'part_name' => 'Toe Kick',
                        'part_type' => 'toe_kick',
                        'position' => ['x' => 0.75, 'y' => -4, 'z' => 0],
                        'dimensions' => ['w' => 34.5, 'h' => 4, 'd' => 0.75],
                        'cut_dimensions' => [
                            'width' => 4,
                            'length' => 34.5,
                            'thickness' => 0.75,
                        ],
                    ],
                ],
            ],
        ];
    }

    // ============================================================
    // Unified Spec Validation Tests
    // ============================================================

    public function test_validate_tcs_metadata_valid(): void
    {
        $metadata = [
            'TCS_PART_ID' => 'AUST-BTH1-B1-C1-LeftSide',
            'TCS_CABINET_ID' => 'AUST-BTH1-B1-C1',
            'TCS_MATERIAL' => '3-4_PreFin',
            'TCS_PART_TYPE' => 'cabinet_box',
            'TCS_THICKNESS' => '0.75',
            'TCS_GRAIN' => 'vertical',
            'TCS_EDGEBAND' => 'F',
        ];

        $result = $this->materialService->validateTcsMetadata($metadata);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_tcs_metadata_missing_required(): void
    {
        $metadata = [
            'TCS_PART_ID' => 'AUST-BTH1-B1-C1-LeftSide',
            // Missing TCS_CABINET_ID, TCS_MATERIAL, etc.
        ];

        $result = $this->materialService->validateTcsMetadata($metadata);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Missing required field: TCS_CABINET_ID', $result['errors']);
        $this->assertContains('Missing required field: TCS_MATERIAL', $result['errors']);
    }

    public function test_validate_tcs_metadata_invalid_material(): void
    {
        $metadata = [
            'TCS_PART_ID' => 'AUST-BTH1-B1-C1-LeftSide',
            'TCS_CABINET_ID' => 'AUST-BTH1-B1-C1',
            'TCS_MATERIAL' => 'InvalidMaterial',
            'TCS_PART_TYPE' => 'cabinet_box',
            'TCS_THICKNESS' => '0.75',
        ];

        $result = $this->materialService->validateTcsMetadata($metadata);

        $this->assertFalse($result['valid']);
        $this->assertContains('Unknown material layer: InvalidMaterial', $result['errors']);
    }

    public function test_validate_tcs_metadata_invalid_edgeband(): void
    {
        $metadata = [
            'TCS_PART_ID' => 'AUST-BTH1-B1-C1-LeftSide',
            'TCS_CABINET_ID' => 'AUST-BTH1-B1-C1',
            'TCS_MATERIAL' => '3-4_PreFin',
            'TCS_PART_TYPE' => 'cabinet_box',
            'TCS_THICKNESS' => '0.75',
            'TCS_EDGEBAND' => 'X,Y,Z', // Invalid codes
        ];

        $result = $this->materialService->validateTcsMetadata($metadata);

        $this->assertFalse($result['valid']);
    }

    public function test_validate_tcs_metadata_cabinet_id_format_warning(): void
    {
        $metadata = [
            'TCS_PART_ID' => '123-LeftSide', // Doesn't follow spec
            'TCS_CABINET_ID' => '123', // Should be XXXX-...
            'TCS_MATERIAL' => '3-4_PreFin',
            'TCS_PART_TYPE' => 'cabinet_box',
            'TCS_THICKNESS' => '0.75',
        ];

        $result = $this->materialService->validateTcsMetadata($metadata);

        $this->assertTrue($result['valid']); // Warnings don't fail validation
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_generate_grasshopper_metadata(): void
    {
        $part = [
            'part_type' => 'cabinet_box',
            'part_name' => 'Left Side',
            'cut_dimensions' => [
                'width' => 17,
                'length' => 27.25,
                'thickness' => 0.75,
            ],
        ];

        $metadata = $this->materialService->generateGrasshopperMetadata(
            $part,
            123, // ERP cabinet ID
            'TCS-001-9AustinFarmRoad',
            'BTH1-B1-C1',
            'left_side'
        );

        // Check standard TCS fields
        $this->assertEquals('AUST-BTH1-B1-C1', $metadata['TCS_CABINET_ID']);
        $this->assertStringStartsWith('AUST-BTH1-B1-C1-', $metadata['TCS_PART_ID']);

        // Check Grasshopper-specific fields
        $this->assertEquals('123', $metadata['TCS_ERP_ID']);
        $this->assertEquals('TCS-001-9AustinFarmRoad', $metadata['TCS_PROJECT_NUMBER']);
        $this->assertEquals('BTH1-B1-C1', $metadata['TCS_CABINET_NUMBER']);
        $this->assertEquals('false', $metadata['TCS_HAS_OVERRIDES']);
        $this->assertEquals('{}', $metadata['TCS_OVERRIDES']);
    }

    public function test_encode_decode_overrides(): void
    {
        $overrides = [
            'width' => 36.5,
            'height' => 30,
            'depth' => 24,
        ];

        $encoded = $this->materialService->encodeOverrides($overrides);
        $decoded = $this->materialService->decodeOverrides($encoded);

        $this->assertEquals($overrides, $decoded);
    }

    public function test_load_unified_config(): void
    {
        $config = $this->materialService->loadUnifiedConfig();

        // Check that config loaded (may be empty if file doesn't exist in test env)
        if (!empty($config)) {
            $this->assertArrayHasKey('version', $config);
            $this->assertArrayHasKey('material_layers', $config);
            $this->assertArrayHasKey('part_type_to_material', $config);
        } else {
            $this->markTestSkipped('Unified config file not found');
        }
    }

    public function test_spec_version(): void
    {
        $version = $this->materialService->getSpecVersion();

        $this->assertEquals('1.0.0', $version);
    }
}

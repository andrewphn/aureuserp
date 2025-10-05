<?php

namespace Tests\Traits;

use App\Services\NutrientService;
use Mockery;

/**
 * Mocks Nutrient SDK for Testing
 *
 * Provides helper methods to mock Nutrient Web SDK responses
 * without requiring the actual SDK license or API calls.
 */
trait MocksNutrientSdk
{
    /**
     * Mock NutrientService for testing
     *
     * @return \Mockery\MockInterface
     */
    protected function mockNutrientService(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(NutrientService::class);

        $this->app->instance(NutrientService::class, $mock);

        return $mock;
    }

    /**
     * Mock successful viewer configuration
     *
     * @return array
     */
    protected function mockViewerConfiguration(): array
    {
        return [
            'container' => '#pdf-viewer',
            'licenseKey' => 'mock-license-key',
            'document' => '/storage/test-document.pdf',
            'instant' => true,
            'theme' => 'LIGHT',
            'toolbarItems' => [
                'sidebar-thumbnails',
                'pager',
                'zoom-out',
                'zoom-in',
                'spacer',
                'annotate',
                'ink',
                'highlighter',
                'text-highlighter',
                'line',
                'arrow',
                'rectangle',
                'ellipse',
                'polygon',
                'polyline',
                'stamp',
                'note',
                'text',
                'spacer',
                'print',
                'download',
            ],
        ];
    }

    /**
     * Mock annotation data in InstantJSON format
     *
     * @param string $annotationId
     * @param string $type
     * @return array
     */
    protected function mockAnnotationInstantJson(string $annotationId, string $type = 'highlight'): array
    {
        return [
            'id' => $annotationId,
            'type' => 'pspdfkit/markup/highlight',
            'pageIndex' => 0,
            'bbox' => [100, 200, 300, 250],
            'rects' => [[100, 200, 300, 250]],
            'color' => '#FFFF00',
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
            'name' => 'Test User',
            'creatorName' => 'Test User',
        ];
    }

    /**
     * Mock multiple annotations
     *
     * @param int $count
     * @return array
     */
    protected function mockMultipleAnnotations(int $count = 5): array
    {
        $annotations = [];

        for ($i = 0; $i < $count; $i++) {
            $annotations[] = $this->mockAnnotationInstantJson("annotation-{$i}", 'highlight');
        }

        return $annotations;
    }

    /**
     * Mock Nutrient SDK initialization JavaScript
     *
     * @return string
     */
    protected function mockNutrientJavaScript(): string
    {
        return <<<'JS'
PSPDFKit.load({
    container: "#pdf-viewer",
    licenseKey: "mock-license-key",
    document: "/storage/test-document.pdf",
    instant: true
}).then(function(instance) {
    window.pdfViewerInstance = instance;
    console.log("PDF Viewer loaded successfully");
});
JS;
    }

    /**
     * Mock annotation creation response
     *
     * @param string $annotationId
     * @return array
     */
    protected function mockAnnotationCreationResponse(string $annotationId): array
    {
        return [
            'success' => true,
            'annotation' => $this->mockAnnotationInstantJson($annotationId),
        ];
    }

    /**
     * Mock annotation sync response
     *
     * @param array $annotations
     * @return array
     */
    protected function mockAnnotationSyncResponse(array $annotations): array
    {
        return [
            'success' => true,
            'annotations' => $annotations,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Setup default Nutrient SDK mocks
     */
    protected function setupDefaultNutrientMocks(): void
    {
        $mock = $this->mockNutrientService();

        $mock->shouldReceive('validateLicenseKey')
            ->andReturn(true);

        $mock->shouldReceive('getViewerConfiguration')
            ->andReturn($this->mockViewerConfiguration());

        $mock->shouldReceive('createViewerInstance')
            ->andReturn($this->mockNutrientJavaScript());

        $mock->shouldReceive('validateFile')
            ->andReturn(true);
    }
}

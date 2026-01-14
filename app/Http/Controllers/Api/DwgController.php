<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DwgService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class DwgController extends Controller
{
    protected DwgService $dwgService;

    public function __construct(DwgService $dwgService)
    {
        $this->dwgService = $dwgService;
    }

    /**
     * Parse an uploaded DWG/DXF file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function parse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:dwg,dxf|max:51200', // Max 50MB
            'output_format' => 'nullable|in:json,svg,geojson',
            'width' => 'nullable|integer|min:100|max:4000',
            'height' => 'nullable|integer|min:100|max:4000',
            'layers' => 'nullable|array',
            'types' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/dwg');
            $fullPath = Storage::path($tempPath);

            // Parse the file
            $data = $this->dwgService->parse($fullPath);

            // Apply filters if specified
            if ($request->has('layers')) {
                $data = $this->dwgService->filterByLayers($data, $request->input('layers'));
            }

            if ($request->has('types')) {
                $data = $this->dwgService->filterByTypes($data, $request->input('types'));
            }

            // Clean up temp file
            Storage::delete($tempPath);

            // Handle output format
            $outputFormat = $request->input('output_format', 'json');

            return match ($outputFormat) {
                'svg' => $this->respondWithSvg($data, $request),
                'geojson' => $this->respondWithGeoJson($data),
                default => $this->respondWithJson($data),
            };
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse a file from storage path
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function parseFromPath(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'disk' => 'nullable|string',
            'output_format' => 'nullable|in:json,svg,geojson',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $disk = $request->input('disk', 'local');
            $path = $request->input('path');

            if (!Storage::disk($disk)->exists($path)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found',
                ], 404);
            }

            $fullPath = Storage::disk($disk)->path($path);
            $data = $this->dwgService->parse($fullPath);

            $outputFormat = $request->input('output_format', 'json');

            return match ($outputFormat) {
                'svg' => $this->respondWithSvg($data, $request),
                'geojson' => $this->respondWithGeoJson($data),
                default => $this->respondWithJson($data),
            };
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert parsed data to SVG
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toSvg(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:dwg,dxf|max:51200',
            'width' => 'nullable|integer|min:100|max:4000',
            'height' => 'nullable|integer|min:100|max:4000',
            'stroke_color' => 'nullable|string',
            'background_color' => 'nullable|string',
            'stroke_width' => 'nullable|numeric|min:0.1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/dwg');
            $fullPath = Storage::path($tempPath);

            $data = $this->dwgService->parse($fullPath);
            
            $svg = $this->dwgService->toSvg($data, [
                'width' => $request->input('width', 800),
                'height' => $request->input('height', 600),
                'strokeColor' => $request->input('stroke_color', '#000000'),
                'backgroundColor' => $request->input('background_color', '#ffffff'),
                'strokeWidth' => $request->input('stroke_width', 1),
            ]);

            Storage::delete($tempPath);

            return response()->json([
                'success' => true,
                'svg' => $svg,
                'stats' => $data['stats'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get layer statistics from a file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function layerStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:dwg,dxf|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/dwg');
            $fullPath = Storage::path($tempPath);

            $data = $this->dwgService->parse($fullPath);
            $stats = $this->dwgService->getLayerStats($data);

            Storage::delete($tempPath);

            return response()->json([
                'success' => true,
                'layers' => $stats,
                'totalEntities' => $data['stats']['entityCount'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if DWG parsing is available
     *
     * @return JsonResponse
     */
    public function capabilities(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'capabilities' => [
                'dxf_native' => true,
                'dwg_libredwg' => $this->dwgService->isLibreDwgAvailable(),
                'dwg_oda' => $this->dwgService->isOdaConverterAvailable(),
                'supported_formats' => ['dxf', 'dwg'],
                'output_formats' => ['json', 'svg', 'geojson'],
                'supported_entities' => DwgService::ENTITY_TYPES,
            ],
        ]);
    }

    /**
     * Respond with JSON data
     */
    protected function respondWithJson(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Respond with SVG
     */
    protected function respondWithSvg(array $data, Request $request): JsonResponse
    {
        $svg = $this->dwgService->toSvg($data, [
            'width' => $request->input('width', 800),
            'height' => $request->input('height', 600),
        ]);

        return response()->json([
            'success' => true,
            'format' => 'svg',
            'svg' => $svg,
            'stats' => $data['stats'],
        ]);
    }

    /**
     * Respond with GeoJSON
     */
    protected function respondWithGeoJson(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'format' => 'geojson',
            'geojson' => $this->dwgService->toGeoJson($data),
            'stats' => $data['stats'],
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Services\DwgService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Exception;

class DwgViewer extends Component
{
    use WithFileUploads;

    public $file;
    public $parsedData = null;
    public $svgContent = null;
    public $layerStats = [];
    public $selectedLayers = [];
    public $selectedTypes = [];
    public $error = null;
    public $isLoading = false;
    public $viewMode = 'svg'; // svg, data, geojson
    public $svgWidth = 800;
    public $svgHeight = 600;
    public $strokeColor = '#000000';
    public $backgroundColor = '#ffffff';

    protected $rules = [
        'file' => 'required|file|mimes:dwg,dxf|max:51200',
    ];

    public function updatedFile()
    {
        $this->validateOnly('file');
        $this->parseFile();
    }

    public function parseFile()
    {
        if (!$this->file) {
            return;
        }

        $this->isLoading = true;
        $this->error = null;
        $this->parsedData = null;
        $this->svgContent = null;

        try {
            $tempPath = $this->file->store('temp/dwg');
            $fullPath = Storage::path($tempPath);

            $dwgService = app(DwgService::class);
            $this->parsedData = $dwgService->parse($fullPath);

            // Get layer statistics
            $this->layerStats = $dwgService->getLayerStats($this->parsedData);

            // Initialize selected layers (all selected by default)
            $this->selectedLayers = array_keys($this->layerStats);

            // Generate SVG
            $this->generateSvg();

            // Clean up
            Storage::delete($tempPath);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateSvg()
    {
        if (!$this->parsedData) {
            return;
        }

        try {
            $dwgService = app(DwgService::class);
            
            // Filter data if layers/types are selected
            $filteredData = $this->parsedData;
            
            if (!empty($this->selectedLayers) && count($this->selectedLayers) < count($this->layerStats)) {
                $filteredData = $dwgService->filterByLayers($filteredData, $this->selectedLayers);
            }

            if (!empty($this->selectedTypes)) {
                $filteredData = $dwgService->filterByTypes($filteredData, $this->selectedTypes);
            }

            $this->svgContent = $dwgService->toSvg($filteredData, [
                'width' => $this->svgWidth,
                'height' => $this->svgHeight,
                'strokeColor' => $this->strokeColor,
                'backgroundColor' => $this->backgroundColor,
            ]);
        } catch (Exception $e) {
            $this->error = 'SVG generation failed: ' . $e->getMessage();
        }
    }

    public function toggleLayer($layerName)
    {
        if (in_array($layerName, $this->selectedLayers)) {
            $this->selectedLayers = array_diff($this->selectedLayers, [$layerName]);
        } else {
            $this->selectedLayers[] = $layerName;
        }
        
        $this->selectedLayers = array_values($this->selectedLayers);
        $this->generateSvg();
    }

    public function selectAllLayers()
    {
        $this->selectedLayers = array_keys($this->layerStats);
        $this->generateSvg();
    }

    public function deselectAllLayers()
    {
        $this->selectedLayers = [];
        $this->generateSvg();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function downloadSvg()
    {
        if (!$this->svgContent) {
            return;
        }

        $filename = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME) . '.svg';
        
        return response()->streamDownload(function () {
            echo $this->svgContent;
        }, $filename, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    public function downloadGeoJson()
    {
        if (!$this->parsedData) {
            return;
        }

        $dwgService = app(DwgService::class);
        $geoJson = json_encode($dwgService->toGeoJson($this->parsedData), JSON_PRETTY_PRINT);
        $filename = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME) . '.geojson';
        
        return response()->streamDownload(function () use ($geoJson) {
            echo $geoJson;
        }, $filename, [
            'Content-Type' => 'application/geo+json',
        ]);
    }

    public function render()
    {
        return view('livewire.dwg-viewer');
    }
}

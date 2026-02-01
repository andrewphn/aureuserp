<?php

namespace Webkul\Project\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * VCarve Setup Sheet Parser
 *
 * Parses VCarve-generated HTML setup sheets to extract:
 * - Material dimensions and settings
 * - Toolpath information
 * - Time estimates
 * - Tool specifications
 */
class VCarveParserService
{
    /**
     * Parse a VCarve HTML setup sheet
     *
     * @param string $htmlContent The HTML content to parse
     * @return array Parsed data
     */
    public function parse(string $htmlContent): array
    {
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        $result = [
            'job_title' => $this->extractJobTitle($doc),
            'sheet_name' => $this->extractSheetName($doc),
            'material' => $this->extractMaterialSetup($doc),
            'toolpaths_summary' => $this->extractToolpathsSummary($doc),
            'toolpaths' => $this->extractToolpaths($doc),
            'total_time_estimate' => null,
        ];

        // Calculate total time from summary if available
        if (!empty($result['toolpaths_summary']['time_estimate'])) {
            $result['total_time_estimate'] = $result['toolpaths_summary']['time_estimate'];
        }

        return $result;
    }

    /**
     * Parse a VCarve HTML file from disk
     *
     * @param string $filePath Path to the HTML file
     * @return array|null Parsed data or null on failure
     */
    public function parseFile(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            Log::warning('VCarve file not found', ['path' => $filePath]);
            return null;
        }

        $content = file_get_contents($filePath);
        if (!$content) {
            return null;
        }

        return $this->parse($content);
    }

    /**
     * Extract the job title from the document
     */
    protected function extractJobTitle(DOMDocument $doc): ?string
    {
        $xpath = new DOMXPath($doc);

        // Look for #jobtitle div
        $nodes = $xpath->query('//*[@id="jobtitle"]');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        return null;
    }

    /**
     * Extract the sheet/layout name
     */
    protected function extractSheetName(DOMDocument $doc): ?string
    {
        $xpath = new DOMXPath($doc);

        // Look for Job Layout title
        $nodes = $xpath->query('//div[contains(@class, "boxtitle") and contains(text(), "Job Layout")]');
        if ($nodes->length > 0) {
            $title = trim($nodes->item(0)->textContent);
            // Extract sheet name from "Job Layout PreFin 13"
            if (preg_match('/Job Layout\s+(.+)/', $title, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract material setup information
     */
    protected function extractMaterialSetup(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $material = [
            'width' => null,
            'height' => null,
            'thickness' => null,
            'z_zero' => null,
            'xy_datum' => null,
        ];

        // Look for Material Block dimensions
        $nodes = $xpath->query('//div[contains(@class, "MaterialBlock")]//span');
        if ($nodes->length > 0) {
            $text = $nodes->item(0)->textContent;
            // Parse "X: 48.5"\nY: 96.5"\nZ: 0.75""
            if (preg_match('/X:\s*([\d.]+)"/', $text, $matches)) {
                $material['width'] = (float) $matches[1];
            }
            if (preg_match('/Y:\s*([\d.]+)"/', $text, $matches)) {
                $material['height'] = (float) $matches[1];
            }
            if (preg_match('/Z:\s*([\d.]+)"/', $text, $matches)) {
                $material['thickness'] = (float) $matches[1];
            }
        }

        // Z-Zero position
        $nodes = $xpath->query('//div[contains(@class, "zzbot")]//span');
        if ($nodes->length > 0) {
            $text = $nodes->item(0)->textContent;
            if (str_contains($text, 'Bottom')) {
                $material['z_zero'] = 'bottom';
            } elseif (str_contains($text, 'Top')) {
                $material['z_zero'] = 'top';
            }
        }

        // XY datum
        $nodes = $xpath->query('//div[contains(@class, "xybl")]//span');
        if ($nodes->length > 0) {
            $text = $nodes->item(0)->textContent;
            if (str_contains($text, 'Bottom Left')) {
                $material['xy_datum'] = 'bottom_left';
            } elseif (str_contains($text, 'Center')) {
                $material['xy_datum'] = 'center';
            }
        }

        return $material;
    }

    /**
     * Extract toolpaths summary
     */
    protected function extractToolpathsSummary(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $summary = [
            'toolpath_count' => 0,
            'time_estimate' => null,
            'time_minutes' => null,
        ];

        // Look for Toolpaths Summary - search in entire document
        $html = $doc->saveHTML();

        // Extract toolpath count from "Toolpaths (N):"
        if (preg_match('/Toolpaths.*?\((\d+)\)/', $html, $matches)) {
            $summary['toolpath_count'] = (int) $matches[1];
        }

        // Extract total time estimate - look for the bold time estimate after Toolpaths Summary
        // Format: Time Estimate: </b>00:21:25
        if (preg_match('/Toolpaths Summary.*?Time Estimate:.*?<\/b>(\d{2}:\d{2}:\d{2})/s', $html, $matches)) {
            $summary['time_estimate'] = $matches[1];
            $summary['time_minutes'] = $this->timeToMinutes($matches[1]);
        }

        return $summary;
    }

    /**
     * Extract individual toolpath details
     */
    protected function extractToolpaths(DOMDocument $doc): array
    {
        $toolpaths = [];
        $html = $doc->saveHTML();

        // Use regex to find toolpath sections - they're in childboxborder divs
        // Pattern: Toolpath: [Name] followed by toolpath details until next toolpath or end
        preg_match_all('/Toolpath:\s*([^<]+)<\/div><div class="boxicon\s+(\w+)".*?(?=Toolpath:|<div id="footer"|$)/s', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = trim($match[1]);
            $iconClass = $match[2] ?? '';
            $section = $match[0];

            // Skip group headers
            if ($iconClass === 'Group') {
                continue;
            }

            $toolpath = [
                'name' => $name,
                'type' => $this->iconToType($iconClass),
                'time_estimate' => null,
                'time_minutes' => null,
                'feed_rate' => null,
                'plunge_rate' => null,
                'spindle_speed' => null,
                'tool' => null,
            ];

            // Extract time estimate from section
            if (preg_match('/Time Estimate:.*?(\d{2}:\d{2}:\d{2})/', $section, $timeMatch)) {
                $toolpath['time_estimate'] = $timeMatch[1];
                $toolpath['time_minutes'] = $this->timeToMinutes($timeMatch[1]);
            }

            // Feed rate
            if (preg_match('/Feed Rate:.*?(\d+)\s*inch\/min/', $section, $feedMatch)) {
                $toolpath['feed_rate'] = (float) $feedMatch[1];
            }

            // Plunge rate
            if (preg_match('/Plunge Rate:.*?(\d+)\s*inch\/min/', $section, $plungeMatch)) {
                $toolpath['plunge_rate'] = (float) $plungeMatch[1];
            }

            // Spindle speed
            if (preg_match('/Spindle Speed:.*?(\d+)/', $section, $spindleMatch)) {
                $toolpath['spindle_speed'] = (int) $spindleMatch[1];
            }

            // Tool name
            if (preg_match('/Tool Name:\s*([^<]+)</', $section, $toolMatch)) {
                $toolpath['tool'] = trim($toolMatch[1]);
            }

            $toolpaths[] = $toolpath;
        }

        return $toolpaths;
    }

    /**
     * Convert icon class to toolpath type
     */
    protected function iconToType(string $iconClass): ?string
    {
        return match (strtolower($iconClass)) {
            'drilling' => 'drilling',
            'profile' => 'profile',
            'pocket' => 'pocket',
            'vcarve' => 'vcarve',
            'layout' => 'layout',
            'group' => null,
            default => null,
        };
    }


    /**
     * Convert time string (HH:MM:SS) to minutes
     */
    protected function timeToMinutes(string $time): float
    {
        $parts = explode(':', $time);
        if (count($parts) !== 3) {
            return 0;
        }

        return (int) $parts[0] * 60 + (int) $parts[1] + (int) $parts[2] / 60;
    }

    /**
     * Calculate estimated completion time based on toolpath times
     *
     * @param array $parsedData The parsed VCarve data
     * @return float Total minutes
     */
    public function calculateTotalTime(array $parsedData): float
    {
        $total = 0;

        foreach ($parsedData['toolpaths'] ?? [] as $toolpath) {
            $total += $toolpath['time_minutes'] ?? 0;
        }

        return $total;
    }

    /**
     * Extract sheet size from material dimensions
     *
     * @param array $material Material array from parse()
     * @return string Sheet size string (e.g., "48x96")
     */
    public function getSheetSizeString(array $material): string
    {
        $width = (int) ($material['width'] ?? 48);
        $height = (int) ($material['height'] ?? 96);

        return "{$width}x{$height}";
    }
}

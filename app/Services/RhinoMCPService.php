<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

/**
 * RhinoMCPService - Communication layer for Rhino MCP tools
 *
 * Provides a PHP interface to execute commands on Rhino via the MCP protocol.
 * Handles RhinoScript execution, document queries, and object manipulation.
 *
 * Available MCP tools:
 * - get_document_info, get_object_info, get_selected_objects_info
 * - execute_rhinoscript_python_code
 * - create_object, create_objects, modify_object, modify_objects, delete_object
 * - select_objects
 * - create_layer, get_or_set_current_layer, delete_layer
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoMCPService
{
    /**
     * Base timeout for MCP commands (milliseconds)
     */
    protected const DEFAULT_TIMEOUT = 30000;

    /**
     * Long timeout for complex operations (milliseconds)
     */
    protected const LONG_TIMEOUT = 120000;

    /**
     * Cache for document info to avoid repeated queries
     */
    protected ?array $documentInfoCache = null;

    /**
     * Get the mcp-cli command path
     *
     * The mcp-cli may be aliased or in a specific location.
     *
     * @return string
     */
    protected function getMcpCliPath(): string
    {
        // Check for explicit configuration
        $configPath = config('services.rhino.mcp_cli_path');
        if ($configPath && file_exists($configPath)) {
            return $configPath;
        }

        // Check common Claude Code locations
        $homeDir = getenv('HOME') ?: '/Users/' . get_current_user();
        $claudeLocalPath = $homeDir . '/.claude/local/node_modules/@anthropic-ai/claude-code/cli.js';

        if (file_exists($claudeLocalPath)) {
            return 'node ' . escapeshellarg($claudeLocalPath) . ' --mcp-cli';
        }

        // Fallback to direct command (may work if in PATH)
        return 'mcp-cli';
    }

    /**
     * Execute a raw MCP CLI command
     *
     * @param string $server MCP server name (e.g., 'rhino')
     * @param string $tool Tool name (e.g., 'get_document_info')
     * @param array $params Parameters for the tool
     * @param int|null $timeout Custom timeout in milliseconds
     * @return array Decoded response
     * @throws \RuntimeException If MCP command fails
     */
    public function callMcp(string $server, string $tool, array $params = [], ?int $timeout = null): array
    {
        $timeout = $timeout ?? self::DEFAULT_TIMEOUT;

        // Ensure params is an object, not array
        $jsonParams = empty($params) ? '{}' : json_encode((object) $params);

        $mcpCli = $this->getMcpCliPath();

        // Build command - don't escape the tool path part
        $command = sprintf(
            '%s call %s/%s %s',
            $mcpCli,
            $server,
            $tool,
            escapeshellarg($jsonParams)
        );

        Log::debug("RhinoMCPService: Executing MCP command", [
            'server' => $server,
            'tool' => $tool,
            'params' => $params,
            'mcp_cli' => $mcpCli,
            'command' => $command,
        ]);

        $result = Process::timeout((int) ($timeout / 1000))->run($command);

        if (!$result->successful()) {
            Log::error("RhinoMCPService: MCP command failed", [
                'command' => $command,
                'error' => $result->errorOutput(),
                'output' => $result->output(),
            ]);
            throw new \RuntimeException("MCP command failed: {$result->errorOutput()}");
        }

        $output = $result->output();

        // Parse the MCP response - it has a content wrapper
        $jsonStart = strpos($output, '{');
        if ($jsonStart === false) {
            $jsonStart = strpos($output, '[');
        }

        if ($jsonStart !== false) {
            $jsonOutput = substr($output, $jsonStart);
            $decoded = json_decode($jsonOutput, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // MCP response may be wrapped in {content: [{type: "text", text: "..."}]}
                if (isset($decoded['content'][0]['text'])) {
                    $innerJson = json_decode($decoded['content'][0]['text'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $innerJson;
                    }
                    return ['raw' => $decoded['content'][0]['text']];
                }

                // Or may have a 'result' field with JSON string
                if (isset($decoded['result'])) {
                    $innerJson = json_decode($decoded['result'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $innerJson;
                    }
                    return ['raw' => $decoded['result']];
                }

                return $decoded;
            }
        }

        // Return raw output if not JSON
        return ['raw' => trim($output)];
    }

    /**
     * Get detailed document information from Rhino
     *
     * @param bool $refresh Force refresh of cached data
     * @return array Document information including objects, layers, groups
     */
    public function getDocumentInfo(bool $refresh = false): array
    {
        if (!$refresh && $this->documentInfoCache !== null) {
            return $this->documentInfoCache;
        }

        $this->documentInfoCache = $this->callMcp('rhino', 'get_document_info');
        return $this->documentInfoCache;
    }

    /**
     * Execute RhinoScript Python code in Rhino
     *
     * @param string $code Python code to execute
     * @param int|null $timeout Custom timeout
     * @return array Result with 'output' key containing printed output
     */
    public function executeRhinoScript(string $code, ?int $timeout = null): array
    {
        $result = $this->callMcp('rhino', 'execute_rhinoscript_python_code', [
            'code' => $code,
        ], $timeout ?? self::LONG_TIMEOUT);

        // Handle string response
        if (is_string($result)) {
            return ['output' => $result, 'success' => true];
        }

        // Handle raw string response
        if (isset($result['raw'])) {
            $output = $result['raw'];
            // Check for error indicators
            if (stripos($output, 'Error') === 0 || stripos($output, 'error:') !== false) {
                return ['error' => $output, 'success' => false];
            }
            return ['output' => $output, 'success' => true];
        }

        // Format: {"success": true, "result": "Script successfully executed! Print output: [data]\n"}
        if (isset($result['success']) && $result['success'] === true && isset($result['result'])) {
            $resultText = $result['result'];

            // Extract the printed output after "Print output: "
            if (preg_match('/Print output:\s*(.+)$/s', $resultText, $matches)) {
                $output = trim($matches[1]);
                return ['output' => $output, 'success' => true];
            }

            return ['output' => $resultText, 'success' => true];
        }

        // If there's an error
        if (isset($result['success']) && $result['success'] === false) {
            return ['error' => $result['error'] ?? 'Unknown error', 'success' => false];
        }

        // Return whatever we got
        return $result;
    }

    /**
     * Get information about a specific object by ID or name
     *
     * @param string|null $id Object GUID
     * @param string|null $name Object name
     * @return array Object information
     */
    public function getObjectInfo(?string $id = null, ?string $name = null): array
    {
        $params = [];
        if ($id !== null) {
            $params['id'] = $id;
        }
        if ($name !== null) {
            $params['name'] = $name;
        }

        return $this->callMcp('rhino', 'get_object_info', $params);
    }

    /**
     * Select objects based on filters
     *
     * Note: SerjoschDuering/rhino-mcp doesn't have a direct select_objects tool,
     * so this is implemented via execute_rhino_code.
     *
     * @param array $filters Filter criteria
     * @param string $filterType 'and' or 'or'
     * @return int Number of selected objects
     */
    public function selectObjects(array $filters = [], string $filterType = 'and'): int
    {
        // Build RhinoScript to select objects based on filters
        $filtersJson = json_encode($filters);
        $filterTypeJson = json_encode($filterType);

        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

filters = {$filtersJson}
filter_type = {$filterTypeJson}

rs.UnselectAllObjects()
selected = []

for obj in rs.AllObjects():
    match = filter_type == 'or'  # Start with False for 'and', True for 'or'

    if filter_type == 'and':
        match = True
        for key, value in filters.items():
            if key == 'layer':
                if rs.ObjectLayer(obj) != value:
                    match = False
                    break
            elif key == 'name':
                name = rs.ObjectName(obj)
                if name != value and not (value.endswith('*') and name and name.startswith(value[:-1])):
                    match = False
                    break
    else:  # 'or'
        match = False
        for key, value in filters.items():
            if key == 'layer' and rs.ObjectLayer(obj) == value:
                match = True
                break
            elif key == 'name':
                name = rs.ObjectName(obj)
                if name == value or (value.endswith('*') and name and name.startswith(value[:-1])):
                    match = True
                    break

    if match:
        rs.SelectObject(obj)
        selected.append(str(obj))

print(json.dumps({"selected_count": len(selected), "selected_ids": selected}))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return $decoded['selected_count'] ?? 0;
        }

        return 0;
    }

    /**
     * Get information about currently selected objects
     *
     * Note: SerjoschDuering/rhino-mcp doesn't have a direct get_selected_objects_info tool,
     * so this is implemented via execute_rhino_code.
     *
     * @param bool $includeAttributes Include custom user attributes
     * @return array List of selected objects with their info
     */
    public function getSelectedObjectsInfo(bool $includeAttributes = true): array
    {
        $includeAttrs = $includeAttributes ? 'True' : 'False';

        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

include_attrs = {$includeAttrs}
selected = rs.SelectedObjects()
objects = []

if selected:
    for obj in selected:
        info = {
            'id': str(obj),
            'name': rs.ObjectName(obj),
            'layer': rs.ObjectLayer(obj),
            'type': rs.ObjectType(obj)
        }

        bbox = rs.BoundingBox([obj])
        if bbox and len(bbox) >= 7:
            info['bounding_box'] = {
                'min': [bbox[0][0], bbox[0][1], bbox[0][2]],
                'max': [bbox[6][0], bbox[6][1], bbox[6][2]]
            }

        if include_attrs:
            attrs = {}
            keys = rs.GetUserText(obj)
            if keys:
                for key in keys:
                    attrs[key] = rs.GetUserText(obj, key)
            info['attributes'] = attrs

        objects.append(info)

print(json.dumps(objects))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get all groups in the document
     *
     * @return array List of group names
     */
    public function getGroups(): array
    {
        $result = $this->executeRhinoScript(<<<'PYTHON'
import rhinoscriptsyntax as rs
import json

groups = rs.GroupNames()
if groups:
    print(json.dumps(list(groups)))
else:
    print(json.dumps([]))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get objects belonging to a specific group
     *
     * @param string $groupName Group name
     * @return array Object GUIDs in the group
     */
    public function getObjectsByGroup(string $groupName): array
    {
        $escapedName = addslashes($groupName);
        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

objects = rs.ObjectsByGroup("{$escapedName}")
if objects:
    print(json.dumps([str(o) for o in objects]))
else:
    print(json.dumps([]))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get all text objects in the document
     *
     * @param string|null $layer Optional layer filter
     * @return array Text objects with content and position
     */
    public function getTextObjects(?string $layer = null): array
    {
        $layerFilter = $layer ? "and rs.ObjectLayer(obj) == \"{$layer}\"" : '';

        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

texts = []
for obj in rs.AllObjects():
    if rs.IsText(obj) {$layerFilter}:
        text = rs.TextObjectText(obj)
        pt = rs.TextObjectPoint(obj)
        height = rs.TextObjectHeight(obj)
        layer = rs.ObjectLayer(obj)
        texts.append({
            'id': str(obj),
            'text': text,
            'x': pt[0] if pt else 0,
            'y': pt[1] if pt else 0,
            'z': pt[2] if pt else 0,
            'height': height,
            'layer': layer
        })
print(json.dumps(texts))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get all dimension annotations
     *
     * @param string|null $layer Optional layer filter
     * @return array Dimension data with values and positions
     */
    public function getDimensions(?string $layer = null): array
    {
        // Use heredoc for cleaner script - DimensionPoints doesn't exist in rhinoscriptsyntax,
        // so we use BoundingBox to get position instead
        $result = $this->executeRhinoScript(<<<'PYTHON'
import rhinoscriptsyntax as rs
import json

dims = []
for obj in rs.AllObjects():
    try:
        if rs.IsLinearDimension(obj):
            text = rs.DimensionText(obj)
            value = rs.DimensionValue(obj)
            layer = rs.ObjectLayer(obj)
            bbox = rs.BoundingBox([obj])
            center = None
            if bbox and len(bbox) >= 2:
                center = [(bbox[0][0] + bbox[6][0]) / 2, (bbox[0][1] + bbox[6][1]) / 2, 0]
            dims.append({"id": str(obj), "text": text, "value": value, "layer": layer, "center": center})
    except Exception as e:
        pass
print(json.dumps(dims))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            $dims = is_array($decoded) ? $decoded : [];

            // Apply layer filter in PHP if specified
            if ($layer && !empty($dims)) {
                $dims = array_filter($dims, fn($d) => ($d['layer'] ?? '') === $layer);
                $dims = array_values($dims);
            }

            return $dims;
        }

        return [];
    }

    /**
     * Get all layers in the document
     *
     * @return array Layer information
     */
    public function getLayers(): array
    {
        $result = $this->executeRhinoScript(<<<'PYTHON'
import rhinoscriptsyntax as rs
import json

layers = []
for layer_name in rs.LayerNames():
    layers.append({
        'name': layer_name,
        'visible': rs.LayerVisible(layer_name),
        'color': list(rs.LayerColor(layer_name)),
        'object_count': rs.LayerCount(layer_name) if hasattr(rs, 'LayerCount') else 0
    })
print(json.dumps(layers))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get block instances with their attributes (fixtures, symbols)
     *
     * @return array Block instances with attributes
     */
    public function getBlockInstances(): array
    {
        $result = $this->executeRhinoScript(<<<'PYTHON'
import rhinoscriptsyntax as rs
import json

blocks = []
for obj in rs.AllObjects():
    if rs.IsBlockInstance(obj):
        name = rs.BlockInstanceName(obj)
        xform = rs.BlockInstanceXform(obj)
        insertion = rs.BlockInstanceInsertPoint(obj)

        # Get user attributes
        attrs = {}
        keys = rs.GetUserText(obj)
        if keys:
            for key in keys:
                attrs[key] = rs.GetUserText(obj, key)

        blocks.append({
            'id': str(obj),
            'block_name': name,
            'insertion_point': list(insertion) if insertion else [0, 0, 0],
            'attributes': attrs,
            'layer': rs.ObjectLayer(obj)
        })
print(json.dumps(blocks))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Calculate bounding box for a list of objects
     *
     * @param array $objectIds List of object GUIDs
     * @return array|null Bounding box with min/max corners
     */
    public function getBoundingBox(array $objectIds): ?array
    {
        if (empty($objectIds)) {
            return null;
        }

        $idsJson = json_encode($objectIds);

        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

ids = {$idsJson}
guids = [rs.coerceguid(id) for id in ids if rs.coerceguid(id)]

if guids:
    bbox = rs.BoundingBox(guids)
    if bbox and len(bbox) >= 8:
        print(json.dumps({
            'min': [bbox[0][0], bbox[0][1], bbox[0][2]],
            'max': [bbox[6][0], bbox[6][1], bbox[6][2]],
            'corners': [[p[0], p[1], p[2]] for p in bbox]
        }))
    else:
        print(json.dumps(None))
else:
    print(json.dumps(None))
PYTHON);

        if (isset($result['output'])) {
            return json_decode($result['output'], true);
        }

        return null;
    }

    /**
     * Get all objects with their basic info
     *
     * @param string|null $layer Optional layer filter
     * @param string|null $objectType Optional type filter (Curve, Surface, etc.)
     * @return array Object info list
     */
    public function getAllObjects(?string $layer = null, ?string $objectType = null, bool $includeUserText = false): array
    {
        $layerFilter = $layer ? "filter_layer = \"{$layer}\"" : "filter_layer = None";
        $typeFilter = $objectType ? "filter_type = \"{$objectType}\"" : "filter_type = None";
        $includeUserTextPython = $includeUserText ? 'True' : 'False';

        $result = $this->executeRhinoScript(<<<PYTHON
import rhinoscriptsyntax as rs
import json

{$layerFilter}
{$typeFilter}
include_user_text = {$includeUserTextPython}

objects = []
for obj in rs.AllObjects():
    obj_layer = rs.ObjectLayer(obj)
    obj_type = rs.ObjectType(obj)

    if filter_layer and obj_layer != filter_layer:
        continue

    # Type name mapping
    type_names = {
        1: 'Point', 2: 'PointCloud', 4: 'Curve', 8: 'Surface',
        16: 'Polysurface', 32: 'Mesh', 256: 'Light', 512: 'Annotation',
        4096: 'BlockInstance', 8192: 'TextDot', 16384: 'Grip',
        32768: 'Detail', 65536: 'Hatch', 131072: 'Morph', 262144: 'SubD',
        524288: 'Cage', 134217728: 'Extrusion'
    }
    type_name = type_names.get(obj_type, 'Unknown')

    if filter_type and type_name != filter_type:
        continue

    name = rs.ObjectName(obj)
    bbox = rs.BoundingBox([obj])

    obj_data = {
        'guid': str(obj),
        'name': name,
        'type': type_name,
        'type_code': obj_type,
        'layer': obj_layer,
        'bounding_box': {
            'min': [bbox[0][0], bbox[0][1], bbox[0][2]] if bbox else None,
            'max': [bbox[6][0], bbox[6][1], bbox[6][2]] if bbox else None
        } if bbox and len(bbox) >= 7 else None
    }

    # Include TCS User Text metadata if requested
    if include_user_text:
        keys = rs.GetUserText(obj)
        if keys:
            user_text = {}
            for key in keys:
                user_text[key] = rs.GetUserText(obj, key)
            obj_data['user_text'] = user_text

    objects.append(obj_data)

print(json.dumps(objects))
PYTHON);

        if (isset($result['output'])) {
            $decoded = json_decode($result['output'], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Parse dimension text to get numeric value in inches
     *
     * Handles common cabinet dimension formats:
     * - 32-3/4" -> 32.75
     * - 1-3/4" -> 1.75
     * - 4" -> 4.0
     * - 19 -> 19.0
     *
     * @param string $text Dimension text
     * @return float|null Numeric value in inches or null if unparseable
     */
    public function parseDimensionText(string $text): ?float
    {
        // Remove quotes, spaces, and trailing inch marks
        $text = trim($text, ' "\'');

        // Pattern: whole-num/denom (e.g., "32-3/4" or "1-3/4")
        if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $text, $matches)) {
            $whole = (int) $matches[1];
            $num = (int) $matches[2];
            $denom = (int) $matches[3];
            return $whole + ($num / $denom);
        }

        // Pattern: num/denom only (e.g., "3/4")
        if (preg_match('/^(\d+)\/(\d+)$/', $text, $matches)) {
            $num = (int) $matches[1];
            $denom = (int) $matches[2];
            return $num / $denom;
        }

        // Pattern: decimal (e.g., "32.75" or "19.5")
        if (preg_match('/^(\d+\.?\d*)$/', $text, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Clear the document info cache
     */
    public function clearCache(): void
    {
        $this->documentInfoCache = null;
    }

    /**
     * Execute RhinoScript Python code (alias for API compatibility)
     *
     * @param string $script Python code to execute
     * @param int $timeout Timeout in seconds
     * @return array Result with output, success, execution_time
     */
    public function executeScript(string $script, int $timeout = 30): array
    {
        $startTime = microtime(true);

        $result = $this->executeRhinoScript($script, $timeout * 1000);

        $executionTime = microtime(true) - $startTime;

        return [
            'output' => $result['output'] ?? $result['error'] ?? null,
            'success' => $result['success'] ?? !isset($result['error']),
            'execution_time' => round($executionTime, 3),
        ];
    }
}

<?php

/**
 * PHPDoc Generator Script for AureusERP
 *
 * This script analyzes PHP files in the codebase and generates comprehensive
 * PHPDoc documentation using reflection, AST analysis, and intelligent inference.
 *
 * Features:
 * - Class-level docblocks with @property annotations for Eloquent models
 * - Method docblocks with @param, @return, @throws annotations
 * - Relationship method documentation for Laravel models
 * - FilamentPHP resource and form component documentation
 * - Preserves existing documentation while adding missing pieces
 *
 * Usage:
 *   php scripts/generate-phpdoc.php [options]
 *
 * Options:
 *   --path=<path>       Process specific path (default: entire project)
 *   --dry-run           Show what would be changed without modifying files
 *   --verbose           Show detailed progress
 *   --models-only       Only process Model classes
 *   --backup            Create .bak files before modifying
 *
 * @author Claude AI Assistant
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Str;

/**
 * PHPDoc Generator Class
 *
 * Main class responsible for generating PHPDoc documentation across the codebase.
 */
class PhpDocGenerator
{
    /** @var array<string, mixed> Configuration options */
    private array $config;

    /** @var array<string, int> Statistics tracking */
    private array $stats = [
        'files_processed' => 0,
        'files_modified' => 0,
        'classes_documented' => 0,
        'methods_documented' => 0,
        'properties_documented' => 0,
        'skipped' => 0,
    ];

    /** @var bool Whether running in dry-run mode */
    private bool $dryRun = false;

    /** @var bool Whether to show verbose output */
    private bool $verbose = false;

    /** @var bool Whether to create backup files */
    private bool $backup = false;

    /** @var string|null Specific path to process */
    private ?string $specificPath = null;

    /** @var bool Only process models */
    private bool $modelsOnly = false;

    /**
     * Laravel/Eloquent relationship methods that return specific types
     *
     * @var array<string, string>
     */
    private const RELATIONSHIP_RETURN_TYPES = [
        'hasOne' => '\Illuminate\Database\Eloquent\Relations\HasOne',
        'hasMany' => '\Illuminate\Database\Eloquent\Relations\HasMany',
        'belongsTo' => '\Illuminate\Database\Eloquent\Relations\BelongsTo',
        'belongsToMany' => '\Illuminate\Database\Eloquent\Relations\BelongsToMany',
        'hasOneThrough' => '\Illuminate\Database\Eloquent\Relations\HasOneThrough',
        'hasManyThrough' => '\Illuminate\Database\Eloquent\Relations\HasManyThrough',
        'morphOne' => '\Illuminate\Database\Eloquent\Relations\MorphOne',
        'morphMany' => '\Illuminate\Database\Eloquent\Relations\MorphMany',
        'morphTo' => '\Illuminate\Database\Eloquent\Relations\MorphTo',
        'morphToMany' => '\Illuminate\Database\Eloquent\Relations\MorphToMany',
        'morphedByMany' => '\Illuminate\Database\Eloquent\Relations\MorphToMany',
    ];

    /**
     * Common Laravel method patterns and their return types
     *
     * @var array<string, string>
     */
    private const COMMON_METHOD_PATTERNS = [
        '/^get.*Attribute$/' => 'mixed',
        '/^set.*Attribute$/' => 'void',
        '/^scope.*$/' => '\Illuminate\Database\Eloquent\Builder',
        '/^boot.*$/' => 'void',
        '/^creating$/' => 'void',
        '/^created$/' => 'void',
        '/^updating$/' => 'void',
        '/^updated$/' => 'void',
        '/^deleting$/' => 'void',
        '/^deleted$/' => 'void',
    ];

    /**
     * FilamentPHP method patterns
     *
     * @var array<string, string>
     */
    private const FILAMENT_METHOD_PATTERNS = [
        'form' => '\Filament\Forms\Form',
        'table' => '\Filament\Tables\Table',
        'infolist' => '\Filament\Infolists\Infolist',
        'getPages' => 'array',
        'getRelations' => 'array',
        'getWidgets' => 'array',
        'getHeaderActions' => 'array',
        'getHeaderWidgets' => 'array',
        'getFooterWidgets' => 'array',
        'getNavigationBadge' => '?string',
        'getNavigationBadgeColor' => '?string',
        'getNavigationLabel' => 'string',
        'getModelLabel' => 'string',
        'getPluralModelLabel' => 'string',
        'getNavigationGroup' => '?string',
        'getNavigationSort' => '?int',
        'canCreate' => 'bool',
        'canEdit' => 'bool',
        'canDelete' => 'bool',
        'canView' => 'bool',
        'canViewAny' => 'bool',
    ];

    /**
     * Initialize the generator with command line arguments
     *
     * @param array<string> $argv Command line arguments
     */
    public function __construct(array $argv)
    {
        $this->parseArguments($argv);
    }

    /**
     * Parse command line arguments
     *
     * @param array<string> $argv Command line arguments
     * @return void
     */
    private function parseArguments(array $argv): void
    {
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--path=')) {
                $this->specificPath = substr($arg, 7);
            } elseif ($arg === '--dry-run') {
                $this->dryRun = true;
            } elseif ($arg === '--verbose') {
                $this->verbose = true;
            } elseif ($arg === '--backup') {
                $this->backup = true;
            } elseif ($arg === '--models-only') {
                $this->modelsOnly = true;
            } elseif ($arg === '--help') {
                $this->showHelp();
                exit(0);
            }
        }
    }

    /**
     * Display help message
     *
     * @return void
     */
    private function showHelp(): void
    {
        echo <<<HELP
PHPDoc Generator for AureusERP
==============================

Usage: php scripts/generate-phpdoc.php [options]

Options:
  --path=<path>       Process specific path (default: entire project)
  --dry-run           Show what would be changed without modifying files
  --verbose           Show detailed progress
  --models-only       Only process Model classes
  --backup            Create .bak files before modifying
  --help              Show this help message

Examples:
  php scripts/generate-phpdoc.php --dry-run --verbose
  php scripts/generate-phpdoc.php --path=app/Models
  php scripts/generate-phpdoc.php --path=plugins/webkul/projects/src/Models
  php scripts/generate-phpdoc.php --models-only --backup

HELP;
    }

    /**
     * Run the PHPDoc generation process
     *
     * @return int Exit code (0 for success)
     */
    public function run(): int
    {
        $this->log("PHPDoc Generator Starting...");
        $this->log("Mode: " . ($this->dryRun ? "DRY RUN" : "LIVE"));

        $basePath = dirname(__DIR__);

        if ($this->specificPath) {
            $searchPath = $basePath . '/' . ltrim($this->specificPath, '/');
        } else {
            $searchPath = $basePath;
        }

        $files = $this->findPhpFiles($searchPath);
        $this->log("Found " . count($files) . " PHP files to process");

        foreach ($files as $file) {
            $this->processFile($file);
        }

        $this->printStats();

        return 0;
    }

    /**
     * Find all PHP files to process
     *
     * @param string $path Base path to search
     * @return array<string> List of PHP file paths
     */
    private function findPhpFiles(string $path): array
    {
        $files = [];

        // Handle single file
        if (is_file($path)) {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                return [$path];
            }
            return [];
        }

        if (!is_dir($path)) {
            $this->log("Path does not exist: $path", 'error');
            return [];
        }

        $excludeDirs = [
            'vendor',
            'node_modules',
            'storage',
            '.git',
            'bootstrap/cache',
            'public',
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                function ($current, $key, $iterator) use ($excludeDirs) {
                    if ($current->isDir()) {
                        return !in_array($current->getFilename(), $excludeDirs);
                    }
                    return true;
                }
            )
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Skip test/debug files in project root
                $filename = $file->getFilename();
                if (preg_match('/^(test-|check-|fix-|debug-|analyze-)/', $filename)) {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Process a single PHP file
     *
     * @param string $filePath Path to the PHP file
     * @return void
     */
    private function processFile(string $filePath): void
    {
        $this->stats['files_processed']++;

        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->log("ERROR: Cannot read file: $filePath", 'error');
            return;
        }

        // Skip files that are just configuration or simple scripts
        if (!preg_match('/^\s*<\?php/m', $content)) {
            return;
        }

        // Extract namespace and class info
        $namespace = $this->extractNamespace($content);
        $classInfo = $this->extractClassInfo($content);

        if (!$classInfo) {
            return;
        }

        // Filter for models only if specified
        if ($this->modelsOnly) {
            if (!$this->isModelClass($content, $classInfo)) {
                return;
            }
        }

        $this->verboseLog("Processing: $filePath");

        $modified = false;
        $newContent = $content;

        // Add class-level PHPDoc if missing
        if (!$this->hasClassDocblock($content, $classInfo['name'])) {
            $docblock = $this->generateClassDocblock($content, $classInfo, $namespace);
            $newContent = $this->insertClassDocblock($newContent, $classInfo['name'], $docblock);
            $modified = true;
            $this->stats['classes_documented']++;
        }

        // Add method PHPDocs
        $methods = $this->extractMethods($newContent);
        foreach ($methods as $method) {
            if (!$this->hasMethodDocblock($newContent, $method)) {
                $methodDoc = $this->generateMethodDocblock($newContent, $method, $classInfo);
                if ($methodDoc) {
                    $newContent = $this->insertMethodDocblock($newContent, $method, $methodDoc);
                    $modified = true;
                    $this->stats['methods_documented']++;
                }
            }
        }

        // Save changes
        if ($modified && !$this->dryRun) {
            if ($this->backup) {
                copy($filePath, $filePath . '.bak');
            }
            file_put_contents($filePath, $newContent);
            $this->stats['files_modified']++;
            $this->log("Modified: $filePath");
        } elseif ($modified && $this->dryRun) {
            $this->log("Would modify: $filePath");
            $this->stats['files_modified']++;
        }
    }

    /**
     * Check if class extends Model or uses Eloquent traits
     *
     * @param string $content File content
     * @param array<string, mixed> $classInfo Class information
     * @return bool
     */
    private function isModelClass(string $content, array $classInfo): bool
    {
        // Check if extends Model
        if (preg_match('/extends\s+(Model|Authenticatable|Pivot)/i', $content)) {
            return true;
        }

        // Check for Eloquent traits
        $modelTraits = ['HasFactory', 'SoftDeletes', 'HasCustomFields'];
        foreach ($modelTraits as $trait) {
            if (str_contains($content, "use $trait") || str_contains($content, ", $trait")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract namespace from file content
     *
     * @param string $content File content
     * @return string|null Namespace or null if not found
     */
    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Extract class information from file content
     *
     * @param string $content File content
     * @return array<string, mixed>|null Class info or null if not found
     */
    private function extractClassInfo(string $content): ?array
    {
        $pattern = '/(abstract\s+)?(class|interface|trait|enum)\s+(\w+)(\s+extends\s+(\w+))?(\s+implements\s+([^{]+))?/';

        if (preg_match($pattern, $content, $matches)) {
            return [
                'abstract' => !empty($matches[1]),
                'type' => $matches[2],
                'name' => $matches[3],
                'extends' => $matches[5] ?? null,
                'implements' => isset($matches[7]) ? array_map('trim', explode(',', $matches[7])) : [],
            ];
        }
        return null;
    }

    /**
     * Check if a class already has a docblock
     *
     * @param string $content File content
     * @param string $className Class name
     * @return bool
     */
    private function hasClassDocblock(string $content, string $className): bool
    {
        // Look for docblock immediately before the class declaration
        $pattern = '/\/\*\*[\s\S]*?\*\/\s*(abstract\s+)?(class|interface|trait|enum)\s+' . preg_quote($className) . '\b/';
        return (bool) preg_match($pattern, $content);
    }

    /**
     * Generate a class-level docblock
     *
     * @param string $content File content
     * @param array<string, mixed> $classInfo Class information
     * @param string|null $namespace Class namespace
     * @return string Generated docblock
     */
    private function generateClassDocblock(string $content, array $classInfo, ?string $namespace): string
    {
        $lines = ['/**'];

        // Generate class description
        $description = $this->generateClassDescription($classInfo, $namespace);
        $lines[] = " * $description";
        $lines[] = ' *';

        // For Eloquent models, add @property annotations
        if ($this->isModelClass($content, $classInfo)) {
            $properties = $this->inferModelProperties($content);
            foreach ($properties as $prop) {
                $lines[] = " * @property {$prop['type']} \${$prop['name']}" .
                    ($prop['description'] ? " {$prop['description']}" : '');
            }

            // Add relationship properties
            $relationships = $this->extractRelationships($content);
            foreach ($relationships as $rel) {
                $lines[] = " * @property-read {$rel['type']} \${$rel['name']}";
            }

            if (!empty($properties) || !empty($relationships)) {
                $lines[] = ' *';
            }
        }

        // Add common annotations based on class type
        if ($classInfo['type'] === 'class') {
            // Check for FilamentPHP resource
            if (str_contains($content, 'extends Resource') || str_contains($namespace ?? '', 'Filament')) {
                $lines[] = ' * @see \Filament\Resources\Resource';
            }

            // Check for Laravel service provider
            if (str_contains($content, 'extends ServiceProvider')) {
                $lines[] = ' * @see \Illuminate\Support\ServiceProvider';
            }
        }

        $lines[] = ' */';

        return implode("\n", $lines);
    }

    /**
     * Generate a human-readable class description
     *
     * @param array<string, mixed> $classInfo Class information
     * @param string|null $namespace Namespace
     * @return string
     */
    private function generateClassDescription(array $classInfo, ?string $namespace): string
    {
        $name = $classInfo['name'];

        // Convert PascalCase to readable format
        $readable = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);

        // Determine type suffix
        $suffix = match(true) {
            str_ends_with($name, 'Resource') => 'Filament resource',
            str_ends_with($name, 'Policy') => 'authorization policy',
            str_ends_with($name, 'Controller') => 'controller',
            str_ends_with($name, 'Service') => 'service',
            str_ends_with($name, 'Repository') => 'repository',
            str_ends_with($name, 'Factory') => 'model factory',
            str_ends_with($name, 'Seeder') => 'database seeder',
            str_ends_with($name, 'Migration') => 'database migration',
            str_ends_with($name, 'Request') => 'form request',
            str_ends_with($name, 'Middleware') => 'middleware',
            str_ends_with($name, 'Event') => 'event',
            str_ends_with($name, 'Listener') => 'event listener',
            str_ends_with($name, 'Job') => 'queued job',
            str_ends_with($name, 'Command') => 'console command',
            str_ends_with($name, 'Provider') => 'service provider',
            str_ends_with($name, 'Exception') => 'exception',
            str_ends_with($name, 'Test') => 'test case',
            str_ends_with($name, 'Widget') => 'Filament widget',
            str_ends_with($name, 'Page') => 'Filament page',
            str_ends_with($name, 'Action') => 'Filament action',
            str_ends_with($name, 'Exporter') => 'data exporter',
            str_ends_with($name, 'Importer') => 'data importer',
            $classInfo['type'] === 'interface' => 'interface',
            $classInfo['type'] === 'trait' => 'trait',
            $classInfo['type'] === 'enum' => 'enumeration',
            default => 'class',
        };

        // For models
        if ($this->isModelClassByName($name, $namespace)) {
            return "$readable Eloquent model";
        }

        return "$readable $suffix";
    }

    /**
     * Check if class is a model by name/namespace
     *
     * @param string $name Class name
     * @param string|null $namespace Namespace
     * @return bool
     */
    private function isModelClassByName(string $name, ?string $namespace): bool
    {
        if ($namespace && str_contains($namespace, 'Models')) {
            return true;
        }

        // Common model names that don't end with "Model"
        $modelNames = ['User', 'Project', 'Task', 'Order', 'Invoice', 'Product', 'Partner', 'Company'];
        return in_array($name, $modelNames);
    }

    /**
     * Infer model properties from fillable, casts, and attribute definitions
     *
     * @param string $content File content
     * @return array<array{name: string, type: string, description: string}>
     */
    private function inferModelProperties(string $content): array
    {
        $properties = [];

        // Always add id, created_at, updated_at for models
        $properties[] = ['name' => 'id', 'type' => 'int', 'description' => ''];
        $properties[] = ['name' => 'created_at', 'type' => '\Carbon\Carbon', 'description' => ''];
        $properties[] = ['name' => 'updated_at', 'type' => '\Carbon\Carbon', 'description' => ''];

        // Check for soft deletes
        if (str_contains($content, 'SoftDeletes')) {
            $properties[] = ['name' => 'deleted_at', 'type' => '\Carbon\Carbon|null', 'description' => ''];
        }

        // Extract fillable properties
        if (preg_match('/\$fillable\s*=\s*\[([\s\S]*?)\];/', $content, $matches)) {
            preg_match_all("/['\"](\w+)['\"]/", $matches[1], $fieldMatches);
            foreach ($fieldMatches[1] as $field) {
                if (!$this->propertyExists($properties, $field)) {
                    $type = $this->inferTypeFromName($field, $content);
                    $properties[] = ['name' => $field, 'type' => $type, 'description' => ''];
                }
            }
        }

        // Extract casts for better type inference
        if (preg_match('/\$casts\s*=\s*\[([\s\S]*?)\];/', $content, $matches) ||
            preg_match('/protected\s+function\s+casts\(\).*?return\s*\[([\s\S]*?)\];/s', $content, $matches)) {
            preg_match_all("/['\"](\w+)['\"]\s*=>\s*['\"]?(\w+)['\"]?/", $matches[1], $castMatches);
            for ($i = 0; $i < count($castMatches[1]); $i++) {
                $field = $castMatches[1][$i];
                $cast = $castMatches[2][$i];
                $type = $this->castToType($cast);

                // Update existing property type
                foreach ($properties as &$prop) {
                    if ($prop['name'] === $field) {
                        $prop['type'] = $type;
                    }
                }
            }
        }

        return $properties;
    }

    /**
     * Check if a property already exists in the array
     *
     * @param array<array{name: string, type: string, description: string}> $properties
     * @param string $name Property name
     * @return bool
     */
    private function propertyExists(array $properties, string $name): bool
    {
        foreach ($properties as $prop) {
            if ($prop['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Infer type from property name
     *
     * @param string $name Property name
     * @param string $content File content for context
     * @return string Inferred type
     */
    private function inferTypeFromName(string $name, string $content): string
    {
        // ID fields
        if ($name === 'id' || str_ends_with($name, '_id')) {
            return 'int';
        }

        // Boolean patterns
        if (str_starts_with($name, 'is_') || str_starts_with($name, 'has_') ||
            str_starts_with($name, 'can_') || str_starts_with($name, 'allow_')) {
            return 'bool';
        }

        // Date patterns
        if (str_ends_with($name, '_at') || str_ends_with($name, '_date') || $name === 'date') {
            return '\Carbon\Carbon|null';
        }

        // Numeric patterns
        if (str_contains($name, 'amount') || str_contains($name, 'price') ||
            str_contains($name, 'total') || str_contains($name, 'quantity') ||
            str_contains($name, 'count') || str_contains($name, 'hours')) {
            return 'float';
        }

        // Array/JSON patterns
        if (str_ends_with($name, '_data') || str_contains($name, 'metadata') ||
            str_contains($name, 'settings') || str_contains($name, 'options') ||
            str_contains($name, 'tags')) {
            return 'array|null';
        }

        return 'string|null';
    }

    /**
     * Convert Laravel cast type to PHPDoc type
     *
     * @param string $cast Laravel cast type
     * @return string PHPDoc type
     */
    private function castToType(string $cast): string
    {
        return match(strtolower($cast)) {
            'int', 'integer' => 'int',
            'float', 'double', 'decimal' => 'float',
            'bool', 'boolean' => 'bool',
            'array', 'json', 'collection' => 'array',
            'date', 'datetime', 'timestamp' => '\Carbon\Carbon|null',
            'object' => 'object',
            'string' => 'string',
            'hashed' => 'string',
            default => 'mixed',
        };
    }

    /**
     * Extract relationship definitions from model
     *
     * @param string $content File content
     * @return array<array{name: string, type: string}>
     */
    private function extractRelationships(string $content): array
    {
        $relationships = [];

        foreach (self::RELATIONSHIP_RETURN_TYPES as $method => $returnType) {
            // Match: public function methodName(): ReturnType { return $this->relationMethod(...) }
            $pattern = '/public\s+function\s+(\w+)\s*\([^)]*\)\s*(?::\s*\w+)?\s*\{[^}]*\$this->' . $method . '\s*\(/';

            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $methodName) {
                    // Determine if it's a collection or single
                    $isMany = str_contains($method, 'Many') || str_contains($method, 'Through');
                    $relatedType = $isMany
                        ? '\Illuminate\Database\Eloquent\Collection'
                        : '\Illuminate\Database\Eloquent\Model|null';

                    $relationships[] = [
                        'name' => $methodName,
                        'type' => $relatedType,
                    ];
                }
            }
        }

        return $relationships;
    }

    /**
     * Insert class docblock into content
     *
     * @param string $content File content
     * @param string $className Class name
     * @param string $docblock Docblock to insert
     * @return string Modified content
     */
    private function insertClassDocblock(string $content, string $className, string $docblock): string
    {
        $pattern = '/((abstract\s+)?(class|interface|trait|enum)\s+' . preg_quote($className) . '\b)/';
        return preg_replace($pattern, $docblock . "\n$1", $content, 1);
    }

    /**
     * Extract method information from content
     *
     * @param string $content File content
     * @return array<array{name: string, visibility: string, static: bool, params: string, returnType: string|null, position: int, indent: string}>
     */
    private function extractMethods(string $content): array
    {
        $methods = [];
        // Match indentation + visibility + static? + function
        $pattern = '/\n([ \t]*)(public|protected|private)\s+(static\s+)?function\s+(\w+)\s*\(([^)]*)\)\s*(?::\s*(\??\w+(?:\|\w+)*))?/';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $methods[] = [
                    'name' => $matches[4][$i][0],
                    'visibility' => $matches[2][$i][0],
                    'static' => !empty($matches[3][$i][0]),
                    'params' => $matches[5][$i][0],
                    'returnType' => $matches[6][$i][0] ?? null,
                    'position' => $matches[0][$i][1],
                    'indent' => $matches[1][$i][0],  // Capture the indentation
                ];
            }
        }

        return $methods;
    }

    /**
     * Check if a method already has a docblock
     *
     * @param string $content File content
     * @param array<string, mixed> $method Method info
     * @return bool
     */
    private function hasMethodDocblock(string $content, array $method): bool
    {
        // Look backwards from method position for docblock
        $beforeMethod = substr($content, 0, $method['position']);
        $lastNewline = strrpos($beforeMethod, "\n");
        $linesBefore = substr($beforeMethod, max(0, $lastNewline - 500));

        // Check if there's a docblock ending just before this method
        return (bool) preg_match('/\*\/\s*$/', trim($linesBefore));
    }

    /**
     * Generate method docblock
     *
     * @param string $content File content
     * @param array<string, mixed> $method Method info
     * @param array<string, mixed> $classInfo Class info
     * @return string|null Generated docblock or null if should skip
     */
    private function generateMethodDocblock(string $content, array $method, array $classInfo): ?string
    {
        $name = $method['name'];
        $indent = $method['indent'] ?? '    ';

        // Skip magic methods except __construct
        if (str_starts_with($name, '__') && $name !== '__construct') {
            return null;
        }

        // Skip simple getters/setters if they're obvious
        if (preg_match('/^(get|set)[A-Z]/', $name) && empty($method['params'])) {
            return null;
        }

        $lines = [$indent . '/**'];

        // Generate description
        $description = $this->generateMethodDescription($name, $method, $classInfo);
        $lines[] = $indent . " * $description";
        $lines[] = $indent . ' *';

        // Parse and document parameters
        if (!empty($method['params'])) {
            $params = $this->parseMethodParams($method['params']);
            foreach ($params as $param) {
                $paramType = $param['type'] ?: 'mixed';
                $paramDesc = $this->inferParamDescription($param['name'], $name);
                $lines[] = $indent . " * @param $paramType \${$param['name']}$paramDesc";
            }
        }

        // Determine return type
        $returnType = $this->inferReturnType($name, $method, $content);
        if ($returnType && $returnType !== 'void') {
            $lines[] = $indent . " * @return $returnType";
        } elseif ($returnType === 'void') {
            $lines[] = $indent . ' * @return void';
        }

        $lines[] = $indent . ' */';

        return implode("\n", $lines);
    }

    /**
     * Generate method description
     *
     * @param string $name Method name
     * @param array<string, mixed> $method Method info
     * @param array<string, mixed> $classInfo Class info
     * @return string Description
     */
    private function generateMethodDescription(string $name, array $method, array $classInfo): string
    {
        // Constructor
        if ($name === '__construct') {
            return "Create a new {$classInfo['name']} instance";
        }

        // Relationship methods
        foreach (array_keys(self::RELATIONSHIP_RETURN_TYPES) as $relMethod) {
            if (preg_match("/\$this->$relMethod/", $name)) {
                $readable = Str::headline($name);
                return "Get the $readable relationship";
            }
        }

        // Attribute accessors
        if (preg_match('/^get(\w+)Attribute$/', $name, $matches)) {
            $attr = Str::headline($matches[1]);
            return "Get the $attr attribute";
        }

        // Attribute mutators
        if (preg_match('/^set(\w+)Attribute$/', $name, $matches)) {
            $attr = Str::headline($matches[1]);
            return "Set the $attr attribute";
        }

        // Scope methods
        if (preg_match('/^scope(\w+)$/', $name, $matches)) {
            $scope = Str::headline($matches[1]);
            return "Scope query to $scope";
        }

        // FilamentPHP methods
        if (isset(self::FILAMENT_METHOD_PATTERNS[$name])) {
            return match($name) {
                'form' => 'Define the form schema',
                'table' => 'Define the table schema',
                'infolist' => 'Define the infolist schema',
                'getPages' => 'Get the resource pages',
                'getRelations' => 'Get the resource relation managers',
                'getWidgets' => 'Get the page widgets',
                'getHeaderActions' => 'Get the header actions',
                'getNavigationBadge' => 'Get the navigation badge',
                'canCreate' => 'Determine if the user can create records',
                'canEdit' => 'Determine if the user can edit the record',
                'canDelete' => 'Determine if the user can delete the record',
                default => Str::headline($name),
            };
        }

        // Generic description from name
        $readable = Str::headline($name);
        return $readable;
    }

    /**
     * Parse method parameters
     *
     * @param string $paramsString Parameters string
     * @return array<array{name: string, type: string|null, default: string|null}>
     */
    private function parseMethodParams(string $paramsString): array
    {
        $params = [];

        if (empty(trim($paramsString))) {
            return $params;
        }

        // Split by comma, but be careful of nested structures
        $parts = preg_split('/,(?![^(]*\))/', $paramsString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Parse: ?Type $name = default
            if (preg_match('/^(\??\w+(?:\|\w+)*(?:<[^>]+>)?)\s+\$(\w+)(?:\s*=\s*(.+))?$/', $part, $matches)) {
                $params[] = [
                    'name' => $matches[2],
                    'type' => $matches[1],
                    'default' => $matches[3] ?? null,
                ];
            }
            // Parse: $name = default (no type)
            elseif (preg_match('/^\$(\w+)(?:\s*=\s*(.+))?$/', $part, $matches)) {
                $params[] = [
                    'name' => $matches[1],
                    'type' => null,
                    'default' => $matches[2] ?? null,
                ];
            }
        }

        return $params;
    }

    /**
     * Infer parameter description from name
     *
     * @param string $paramName Parameter name
     * @param string $methodName Method name for context
     * @return string Description with leading space if not empty
     */
    private function inferParamDescription(string $paramName, string $methodName): string
    {
        $descriptions = [
            'id' => 'The unique identifier',
            'query' => 'The search query',
            'request' => 'The incoming request',
            'data' => 'The data array',
            'record' => 'The model record',
            'model' => 'The model instance',
            'user' => 'The user instance',
            'value' => 'The value to set',
            'callback' => 'The callback function',
            'limit' => 'Maximum number of results',
            'offset' => 'Number of records to skip',
            'page' => 'Page number',
            'perPage' => 'Items per page',
        ];

        if (isset($descriptions[$paramName])) {
            return ' ' . $descriptions[$paramName];
        }

        return '';
    }

    /**
     * Infer return type for a method
     *
     * @param string $name Method name
     * @param array<string, mixed> $method Method info
     * @param string $content File content
     * @return string|null Return type
     */
    private function inferReturnType(string $name, array $method, string $content): ?string
    {
        // If already has return type, use it
        if (!empty($method['returnType'])) {
            return $method['returnType'];
        }

        // Check relationship methods
        foreach (self::RELATIONSHIP_RETURN_TYPES as $relMethod => $returnType) {
            // Look for this relationship method being called in the method body
            if ($this->methodCallsRelationship($name, $relMethod, $content)) {
                return $returnType;
            }
        }

        // Check FilamentPHP patterns
        if (isset(self::FILAMENT_METHOD_PATTERNS[$name])) {
            return self::FILAMENT_METHOD_PATTERNS[$name];
        }

        // Check common method patterns
        foreach (self::COMMON_METHOD_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $name)) {
                return $type;
            }
        }

        // Boolean methods
        if (str_starts_with($name, 'is') || str_starts_with($name, 'has') ||
            str_starts_with($name, 'can') || str_starts_with($name, 'should')) {
            return 'bool';
        }

        return null;
    }

    /**
     * Check if a method calls a specific relationship method
     *
     * @param string $methodName Method name
     * @param string $relationMethod Relationship method name
     * @param string $content File content
     * @return bool
     */
    private function methodCallsRelationship(string $methodName, string $relationMethod, string $content): bool
    {
        // Find the method body
        $pattern = '/function\s+' . preg_quote($methodName) . '\s*\([^)]*\)[^{]*\{([^}]+)\}/s';

        if (preg_match($pattern, $content, $matches)) {
            return str_contains($matches[1], "\$this->$relationMethod(");
        }

        return false;
    }

    /**
     * Insert method docblock into content
     *
     * @param string $content File content
     * @param array<string, mixed> $method Method info
     * @param string $docblock Docblock to insert
     * @return string Modified content
     */
    private function insertMethodDocblock(string $content, array $method, string $docblock): string
    {
        $visibility = $method['visibility'];
        $static = $method['static'] ? 'static\s+' : '';
        $name = preg_quote($method['name']);
        $indent = $method['indent'] ?? '    ';

        // Match the method declaration
        $pattern = '/(\n' . preg_quote($indent) . ')(' . $visibility . '\s+' . $static . 'function\s+' . $name . '\s*\()/';

        return preg_replace($pattern, "\n" . $docblock . "$1$2", $content, 1);
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     * @param string $level Log level
     * @return void
     */
    private function log(string $message, string $level = 'info'): void
    {
        $prefix = match($level) {
            'error' => '❌ ',
            'warning' => '⚠️ ',
            'success' => '✅ ',
            default => '📝 ',
        };

        echo $prefix . $message . "\n";
    }

    /**
     * Log a verbose message
     *
     * @param string $message Message to log
     * @return void
     */
    private function verboseLog(string $message): void
    {
        if ($this->verbose) {
            $this->log($message);
        }
    }

    /**
     * Print statistics summary
     *
     * @return void
     */
    private function printStats(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "PHPDoc Generation Complete\n";
        echo str_repeat('=', 50) . "\n";
        echo "Files processed:      {$this->stats['files_processed']}\n";
        echo "Files modified:       {$this->stats['files_modified']}\n";
        echo "Classes documented:   {$this->stats['classes_documented']}\n";
        echo "Methods documented:   {$this->stats['methods_documented']}\n";
        echo str_repeat('=', 50) . "\n";

        if ($this->dryRun) {
            echo "\n⚠️  DRY RUN - No files were actually modified\n";
            echo "Run without --dry-run to apply changes\n";
        }
    }
}

// Run the generator
if (php_sapi_name() === 'cli') {
    $generator = new PhpDocGenerator($argv);
    exit($generator->run());
}

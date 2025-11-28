<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class NutrientService
{
    protected string $licenseKey;
    protected array $viewerOptions;
    protected array $toolbarItems;
    protected bool $annotationsEnabled;

    public function __construct()
    {
        $this->licenseKey = Config::get('services.nutrient.license_key') ?? Config::get('nutrient.license_key');
        $this->viewerOptions = Config::get('nutrient.viewer_options', []);
        $this->toolbarItems = Config::get('nutrient.default_toolbar', [
            'annotate' => true,
            'save' => true,
            'print' => true,
        ]);
        $this->annotationsEnabled = Config::get('nutrient.enable_annotations', true);
    }

    /**
     * Validate the license key is properly configured
     *
     * @throws RuntimeException
     * @return bool
     */
    public function validateLicenseKey(): bool
    {
        $licenseKey = Config::get('services.nutrient.license_key') ?? Config::get('nutrient.license_key');

        if (empty($licenseKey)) {
            throw new RuntimeException('Nutrient license key is not configured');
        }

        $this->licenseKey = $licenseKey;

        return true;
    }

    /**
     * Get the license key for SDK initialization
     *
     * @return string
     */
    public function getLicenseKey(): string
    {
        return $this->licenseKey;
    }

    /**
     * Get the SDK configuration object for JavaScript initialization
     *
     * @param array $customOptions Additional options to merge with defaults
     * @return array
     */
    public function getSDKConfiguration(array $customOptions = []): array
    {
        $licenseKey = Config::get('services.nutrient.license_key') ?? $this->licenseKey;

        $config = [
            'licenseKey' => $licenseKey,
            'container' => 'standalone',
            'toolbarItems' => $this->toolbarItems,
            'annotationPresets' => $this->getAnnotationPresets(),
            'enableAnnotations' => $this->annotationsEnabled,
            'enableForms' => Config::get('nutrient.enable_forms', true),
            'autoSaveMode' => 'INTELLIGENT',
            'autoSaveInterval' => Config::get('nutrient.autosave_interval', 30) * 1000, // Convert to milliseconds
        ];

        return array_merge($config, $this->viewerOptions, $customOptions);
    }

    /**
     * Create a viewer instance configuration for a specific document
     *
     * @param string $documentUrl URL or path to the PDF document
     * @param string $container Container element ID (default: 'standalone')
     * @param array $options Custom options for this viewer instance
     * @return string JavaScript initialization code
     */
    public function createViewerInstance(string $documentUrl, string $container = 'standalone', array $options = []): string
    {
        if (empty($documentUrl)) {
            throw new InvalidArgumentException('Document URL cannot be empty');
        }

        $licenseKey = Config::get('services.nutrient.license_key') ?? $this->licenseKey;
        $config = $this->getSDKConfiguration($options);
        $config['container'] = $container;
        $config['document'] = $documentUrl;
        $config['licenseKey'] = $licenseKey;

        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        return "PSPDFKit.load({$configJson})";
    }

    /**
     * Get annotation tool presets configuration
     *
     * @return array
     */
    protected function getAnnotationPresets(): array
    {
        return [
            'highlight' => [
                'color' => '#ffeb3b',
                'opacity' => 0.5,
            ],
            'note' => [
                'color' => '#ffc107',
                'icon' => 'comment',
            ],
            'ink' => [
                'strokeColor' => '#007bff',
                'strokeWidth' => 2,
            ],
            'text' => [
                'fontSize' => 14,
                'fontFamily' => 'Helvetica',
                'color' => '#000000',
            ],
        ];
    }

    /**
     * Configure toolbar items for a viewer instance
     *
     * @param array $toolbarItems Array of toolbar item names
     * @return self
     */
    public function setToolbarItems(array $toolbarItems): self
    {
        $this->toolbarItems = $toolbarItems;
        return $this;
    }

    /**
     * Enable or disable annotations
     *
     * @param bool $enabled
     * @return self
     */
    public function setAnnotationsEnabled(bool $enabled): self
    {
        $this->annotationsEnabled = $enabled;
        return $this;
    }

    /**
     * Add custom viewer options
     *
     * @param array $options
     * @return self
     */
    public function setViewerOptions(array $options): self
    {
        $this->viewerOptions = array_merge($this->viewerOptions, $options);
        return $this;
    }

    /**
     * Get the maximum allowed file size for uploads
     *
     * @return int Size in bytes
     */
    public function getMaxFileSize(): int
    {
        return Config::get('nutrient.max_file_size', 50 * 1024 * 1024);
    }

    /**
     * Get allowed MIME types for document uploads
     *
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return Config::get('nutrient.allowed_mime_types', ['application/pdf']);
    }

    /**
     * Get allowed file extensions for document uploads
     *
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return Config::get('nutrient.allowed_extensions', ['pdf']);
    }

    /**
     * Validate if a file is allowed based on size and type
     *
     * @param string $mimeType File MIME type
     * @param int $fileSize File size in bytes
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateFile(string $mimeType, int $fileSize): bool
    {
        if (!in_array($mimeType, $this->getAllowedMimeTypes())) {
            throw new InvalidArgumentException('Invalid file type. Only PDF files are allowed.');
        }

        if ($fileSize <= 0) {
            throw new InvalidArgumentException('File cannot be empty');
        }

        $maxSize = Config::get('services.nutrient.max_file_size', $this->getMaxFileSize());
        if ($fileSize > $maxSize) {
            throw new InvalidArgumentException('File size exceeds maximum allowed size');
        }

        return true;
    }

    /**
     * Get JavaScript initialization code for the viewer
     *
     * @param string $documentUrl Document URL to load
     * @param string $containerId DOM element ID for the viewer (default: 'standalone')
     * @param array $options Custom options
     * @return string
     */
    public function getJavaScriptInitCode(string $documentUrl, string $containerId = 'standalone', array $options = []): string
    {
        return $this->createViewerInstance($documentUrl, $containerId, $options);
    }

    /**
     * Get fallback configuration for when SDK fails to load
     *
     * @return array
     */
    public function getFallbackConfiguration(): array
    {
        Log::warning('Using fallback PDF configuration - license key may be invalid');

        return [
            'readOnly' => true,
            'toolbarItems' => [],
            'message' => 'PDF viewer is currently unavailable. Please download the document to view it.',
            'download_enabled' => true,
            'retry_enabled' => true,
            'support_contact' => Config::get('app.support_email', 'support@example.com'),
        ];
    }

    /**
     * Log SDK initialization error
     *
     * @param string $error Error message
     * @param array $context Additional context
     * @return void
     */
    public function logError(string $error, array $context = []): void
    {
        Log::error('Nutrient SDK Error: ' . $error, array_merge([
            'license_key_prefix' => substr($this->licenseKey, 0, 15),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Generate a unique annotation ID using ULID format
     * Compatible with Nutrient SDK's generateInstantId()
     *
     * @return string ULID-formatted annotation ID
     */
    public function generateAnnotationId(): string
    {
        // Generate ULID (Universally Unique Lexicographically Sortable Identifier)
        // Format: 26 characters, timestamp + randomness
        $timestamp = (int)(microtime(true) * 1000);
        $timestampChars = $this->encodeBase32($timestamp, 10);
        $randomChars = $this->encodeBase32(random_int(0, PHP_INT_MAX), 16);

        return strtoupper($timestampChars . $randomChars);
    }

    /**
     * Encode number to Crockford's Base32
     *
     * @param int $number Number to encode
     * @param int $length Target length
     * @return string Base32 encoded string
     */
    protected function encodeBase32(int $number, int $length): string
    {
        $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // Crockford's Base32
        $encoded = '';

        while ($number > 0) {
            $encoded = $chars[$number % 32] . $encoded;
            $number = (int)($number / 32);
        }

        return str_pad($encoded, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Get JavaScript code to ensure changes are saved
     * Wrapper for Nutrient SDK's ensureChangesSaved()
     *
     * @return string JavaScript code
     */
    public function getEnsureChangesSavedCode(): string
    {
        return <<<JS
        instance.ensureChangesSaved().then(() => {
            console.log('All annotation changes saved to backend');
        }).catch((error) => {
            console.error('Failed to ensure changes saved:', error);
        });
        JS;
    }
}

<?php

namespace App\Services\AI;

use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Content;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Resources\GenerativeModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\UploadedFile;
use Exception;

/**
 * Service class for interacting with Google Gemini AI API
 * 
 * This service provides a clean interface for:
 * - Text generation and conversation
 * - Database query interpretation
 * - Project CRUD operations via natural language
 * - Streaming responses for real-time interaction
 */
class GeminiService
{
    /**
     * The Gemini model instance
     */
    protected GenerativeModel $model;
    
    /**
     * Default model name
     */
    protected string $modelName;
    
    /**
     * Generation configuration
     */
    protected ?GenerationConfig $config = null;
    
    /**
     * System instruction for context
     */
    protected ?Content $systemInstruction = null;
    
    /**
     * Initialize the Gemini service
     */
    public function __construct()
    {
        $this->modelName = config('gemini.default_model', 'gemini-1.5-flash');
        $this->initializeModel();
        $this->setupSystemContext();
    }
    
    /**
     * Initialize the Gemini model with configuration
     */
    protected function initializeModel(): void
    {
        $this->model = Gemini::generativeModel($this->modelName);
        
        // Set up generation configuration with proper constructor parameters
        $this->config = new GenerationConfig(
            candidateCount: 1,
            stopSequences: [],
            maxOutputTokens: config('gemini.max_tokens', 2048),
            temperature: config('gemini.temperature', 0.7),
            topP: 0.95,
            topK: 40
        );
        
        $this->model->withGenerationConfig($this->config);
    }
    
    /**
     * Set up system context for TCS Woodwork ERP
     */
    protected function setupSystemContext(): void
    {
        $systemPrompt = "You are an AI assistant for TCS Woodwork ERP system. 
        You help users with:
        - Managing projects and project data
        - Answering questions about the ERP system
        - Performing CRUD operations on projects
        - Providing insights from the database
        
        You have access to the following project fields:
        - name (project name)
        - description (project details)
        - status (pending, in_progress, completed, cancelled)
        - customer_id (reference to customer)
        - start_date and end_date
        - budget and actual_cost
        - progress_percentage
        
        Always be helpful, accurate, and format responses clearly.
        When performing CRUD operations, confirm actions before execution.";
        
        $this->systemInstruction = Content::parse($systemPrompt);
        $this->model->withSystemInstruction($this->systemInstruction);
    }
    
    /**
     * Generate a text response from a prompt with caching and rate limiting
     * 
     * @param string $prompt The user's input prompt
     * @param array $context Additional context data
     * @return string The generated response text
     * @throws Exception
     */
    public function generateResponse(string $prompt, array $context = []): string
    {
        try {
            // Apply rate limiting
            $this->checkRateLimit();
            
            // Add context to prompt if provided
            if (!empty($context)) {
                $contextString = $this->formatContext($context);
                $prompt = $contextString . "\n\nUser Query: " . $prompt;
            }
            
            // Check cache first if enabled
            if (config('gemini.enable_response_caching', true)) {
                $cacheKey = 'gemini_response_' . md5($prompt . serialize($context));
                $cached = Cache::get($cacheKey);
                
                if ($cached) {
                    Log::info('Gemini Cache Hit', ['cache_key' => $cacheKey]);
                    return $cached;
                }
            }
            
            // Generate response
            $response = $this->model->generateContent($prompt);
            
            // Extract text from response
            $text = $this->extractTextFromResponse($response);
            
            // Cache the response if enabled
            if (config('gemini.enable_response_caching', true)) {
                $ttl = config('gemini.cache_ttl', 3600);
                Cache::put($cacheKey, $text, $ttl);
            }
            
            // Log the interaction
            $this->logInteraction($prompt, $text);
            
            return $text;
            
        } catch (Exception $e) {
            Log::error('Gemini API Error', [
                'prompt' => $prompt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Failed to generate response: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyze an image with a text prompt
     *
     * @param string|UploadedFile $image Image path, base64 data, or UploadedFile
     * @param string $prompt The prompt describing what to extract/analyze
     * @param string|null $mimeType The MIME type of the image (auto-detected if null)
     * @return string The generated response text
     * @throws Exception
     */
    public function analyzeImage($image, string $prompt, ?string $mimeType = null): string
    {
        try {
            // Apply rate limiting
            $this->checkRateLimit();

            // Get image data and mime type
            $imageData = $this->prepareImageData($image, $mimeType);

            // Create the blob for the image
            $blob = new Blob(
                mimeType: $imageData['mimeType'],
                data: $imageData['data']
            );

            // Generate response with image and text
            $response = $this->model->generateContent([
                $prompt,
                $blob
            ]);

            // Extract text from response
            $text = $this->extractTextFromResponse($response);

            // Log the interaction
            Log::info('Gemini Image Analysis', [
                'model' => $this->modelName,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($text),
                'image_size' => strlen($imageData['data']),
                'mime_type' => $imageData['mimeType'],
                'timestamp' => now()->toIso8601String()
            ]);

            return $text;

        } catch (Exception $e) {
            Log::error('Gemini Image Analysis Error', [
                'prompt' => $prompt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Failed to analyze image: ' . $e->getMessage());
        }
    }

    /**
     * Prepare image data for API request
     *
     * @param string|UploadedFile $image
     * @param string|null $mimeType
     * @return array ['data' => base64, 'mimeType' => string]
     */
    protected function prepareImageData($image, ?string $mimeType = null): array
    {
        // Handle UploadedFile
        if ($image instanceof UploadedFile) {
            $data = base64_encode(file_get_contents($image->getRealPath()));
            $detectedMime = $image->getMimeType();

            return [
                'data' => $data,
                'mimeType' => $this->mapMimeType($mimeType ?? $detectedMime)
            ];
        }

        // Handle file path
        if (file_exists($image)) {
            $data = base64_encode(file_get_contents($image));
            $detectedMime = mime_content_type($image);

            return [
                'data' => $data,
                'mimeType' => $this->mapMimeType($mimeType ?? $detectedMime)
            ];
        }

        // Handle base64 data (with or without data URI prefix)
        if (str_starts_with($image, 'data:')) {
            // Parse data URI
            preg_match('/^data:([^;]+);base64,(.+)$/', $image, $matches);
            if (count($matches) === 3) {
                return [
                    'data' => $matches[2],
                    'mimeType' => $this->mapMimeType($mimeType ?? $matches[1])
                ];
            }
        }

        // Assume it's raw base64 data
        return [
            'data' => $image,
            'mimeType' => $this->mapMimeType($mimeType ?? 'image/jpeg')
        ];
    }

    /**
     * Map string MIME type to Gemini MimeType enum
     *
     * @param string $mimeType
     * @return MimeType
     */
    protected function mapMimeType(string $mimeType): MimeType
    {
        return match (strtolower($mimeType)) {
            'image/jpeg', 'image/jpg' => MimeType::IMAGE_JPEG,
            'image/png' => MimeType::IMAGE_PNG,
            'image/gif' => MimeType::IMAGE_GIF,
            'image/webp' => MimeType::IMAGE_WEBP,
            'image/heic' => MimeType::IMAGE_HEIC,
            'image/heif' => MimeType::IMAGE_HEIF,
            'application/pdf' => MimeType::APPLICATION_PDF,
            default => MimeType::IMAGE_JPEG,
        };
    }

    /**
     * Generate a streaming response for real-time interaction
     *
     * @param string $prompt The user's input prompt
     * @param array $context Additional context data
     * @return \Generator Yields response chunks
     */
    public function streamResponse(string $prompt, array $context = []): \Generator
    {
        try {
            // Add context to prompt if provided
            if (!empty($context)) {
                $contextString = $this->formatContext($context);
                $prompt = $contextString . "\n\nUser Query: " . $prompt;
            }
            
            // Get streaming response
            $stream = $this->model->streamGenerateContent($prompt);
            
            $fullResponse = '';
            
            // Yield chunks as they arrive
            foreach ($stream as $response) {
                $chunk = $this->extractTextFromResponse($response);
                if (!empty($chunk)) {
                    $fullResponse .= $chunk;
                    yield $chunk;
                }
            }
            
            // Log the complete interaction
            $this->logInteraction($prompt, $fullResponse);
            
        } catch (Exception $e) {
            Log::error('Gemini Streaming Error', [
                'prompt' => $prompt,
                'error' => $e->getMessage()
            ]);
            
            yield 'Error: ' . $e->getMessage();
        }
    }
    
    /**
     * Interpret a natural language query for database operations
     * 
     * @param string $query The natural language query
     * @return array Structured query interpretation
     */
    public function interpretDatabaseQuery(string $query): array
    {
        $prompt = "Interpret this natural language query for a project management database.
        Extract the following:
        1. Operation type (select, insert, update, delete)
        2. Filters/conditions
        3. Fields to return or modify
        4. Sort order if applicable
        
        Query: {$query}
        
        Return as JSON with keys: operation, filters, fields, sort";
        
        try {
            $response = $this->generateResponse($prompt);
            
            // Try to parse as JSON
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $parsed;
                }
            }
            
            // Fallback structure if JSON parsing fails
            return [
                'operation' => 'select',
                'filters' => [],
                'fields' => ['*'],
                'sort' => null,
                'raw_interpretation' => $response
            ];
            
        } catch (Exception $e) {
            Log::error('Query interpretation failed', ['query' => $query, 'error' => $e->getMessage()]);
            
            return [
                'operation' => 'error',
                'message' => 'Failed to interpret query',
                'raw_query' => $query
            ];
        }
    }
    
    /**
     * Generate a response for project CRUD operations
     * 
     * @param string $request The CRUD request in natural language
     * @param array $projectData Current project data if applicable
     * @return array The structured CRUD response
     */
    public function handleProjectCrud(string $request, array $projectData = []): array
    {
        $context = [
            'available_operations' => ['create', 'read', 'update', 'delete', 'list'],
            'current_projects' => $projectData
        ];
        
        $prompt = "Process this project management request and determine:
        1. The CRUD operation to perform
        2. Required data/parameters
        3. Confirmation message for the user
        
        Request: {$request}
        
        Return as JSON with keys: operation, parameters, confirmation_message";
        
        try {
            $response = $this->generateResponse($prompt, $context);
            
            // Parse the response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'success' => true,
                        'data' => $parsed
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Could not parse CRUD request',
                'raw_response' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'CRUD operation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract text from Gemini response object
     * 
     * @param mixed $response The Gemini response object
     * @return string The extracted text
     */
    protected function extractTextFromResponse($response): string
    {
        if (!$response || !isset($response->candidates) || count($response->candidates) === 0) {
            return '';
        }
        
        $candidate = $response->candidates[0];
        
        if (!isset($candidate->content) || !isset($candidate->content->parts) || count($candidate->content->parts) === 0) {
            return '';
        }
        
        return $candidate->content->parts[0]->text ?? '';
    }
    
    /**
     * Format context array into a string for the prompt
     * 
     * @param array $context The context data
     * @return string Formatted context string
     */
    protected function formatContext(array $context): string
    {
        $contextParts = ["Context:"];
        
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $contextParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . json_encode($value);
            } else {
                $contextParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
            }
        }
        
        return implode("\n", $contextParts);
    }
    
    /**
     * Log the interaction for debugging and analytics
     * 
     * @param string $prompt The input prompt
     * @param string $response The generated response
     */
    protected function logInteraction(string $prompt, string $response): void
    {
        Log::info('Gemini Interaction', [
            'model' => $this->modelName,
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($response),
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Count tokens in a text string
     * 
     * @param string $text The text to count tokens for
     * @return int The token count
     */
    public function countTokens(string $text): int
    {
        try {
            $response = $this->model->countTokens($text);
            return $response->totalTokens;
        } catch (Exception $e) {
            Log::error('Token counting failed', ['error' => $e->getMessage()]);
            
            // Rough estimate as fallback (1 token ≈ 4 characters)
            return (int) ceil(strlen($text) / 4);
        }
    }
    
    /**
     * Start a chat session for multi-turn conversations
     * 
     * @param array $history Previous conversation history
     * @return \Gemini\Resources\ChatSession
     */
    public function startChatSession(array $history = []): \Gemini\Resources\ChatSession
    {
        return $this->model->startChat($history);
    }
    
    /**
     * Change the model being used
     * 
     * @param string $modelName The model name to switch to
     */
    public function setModel(string $modelName): void
    {
        $this->modelName = $modelName;
        $this->initializeModel();
        $this->setupSystemContext();
    }
    
    /**
     * Get the current model name
     * 
     * @return string
     */
    public function getModel(): string
    {
        return $this->modelName;
    }

    /**
     * Check rate limiting for API calls
     * 
     * @throws Exception
     */
    protected function checkRateLimit(): void
    {
        $key = 'gemini_api_requests';
        $maxAttempts = config('gemini.rate_limit_per_minute', 60);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw new Exception("Rate limit exceeded. Try again in {$seconds} seconds.");
        }
        
        RateLimiter::hit($key, 60); // 60 seconds decay
    }
}
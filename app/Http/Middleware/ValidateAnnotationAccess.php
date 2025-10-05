<?php

namespace App\Http\Middleware;

use App\Models\PdfDocument;
use App\Models\PdfAnnotation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ValidateAnnotationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated'
            ], 401);
        }

        // Get document ID from route parameters
        $documentId = $request->route('documentId');
        $annotationId = $request->route('annotationId');

        try {
            // Validate document access if documentId is present
            if ($documentId) {
                $document = PdfDocument::findOrFail($documentId);

                // Check if user has access to document
                if (!$user->can('view', $document)) {
                    Log::warning('Unauthorized document access attempt', [
                        'user_id' => $user->id,
                        'document_id' => $documentId,
                        'ip' => $request->ip()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to document'
                    ], 403);
                }

                // Store document in request for controller use
                $request->attributes->set('document', $document);
            }

            // Validate annotation access if annotationId is present
            if ($annotationId) {
                $annotation = PdfAnnotation::with('document')->findOrFail($annotationId);

                // Check if user has access to the annotation's document
                if (!$user->can('view', $annotation->document)) {
                    Log::warning('Unauthorized annotation access attempt', [
                        'user_id' => $user->id,
                        'annotation_id' => $annotationId,
                        'document_id' => $annotation->document_id,
                        'ip' => $request->ip()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to annotation'
                    ], 403);
                }

                // For write operations, check update permission
                if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
                    if (!$user->can('update', $annotation->document)) {
                        Log::warning('Unauthorized annotation modification attempt', [
                            'user_id' => $user->id,
                            'annotation_id' => $annotationId,
                            'method' => $request->method(),
                            'ip' => $request->ip()
                        ]);

                        return response()->json([
                            'success' => false,
                            'error' => 'Unauthorized to modify annotation'
                        ], 403);
                    }
                }

                // Store annotation in request for controller use
                $request->attributes->set('annotation', $annotation);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Resource not found'
            ], 404);
        }

        return $next($request);
    }
}

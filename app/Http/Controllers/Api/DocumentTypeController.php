<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Models\AuditLog;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;

class DocumentTypeController extends Controller
{
    /**
     * List all document types (no pagination — admin catalog).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $types = DocumentType::all();

        return response()->json([
            'data'    => DocumentTypeResource::collection($types),
            'message' => 'Tipos de documento obtenidos correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Create a new document type.
     *
     * @param  StoreDocumentTypeRequest $request
     * @return JsonResponse
     */
    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $documentType = DocumentType::create($request->validated());

        AuditLog::log(
            'document_type.created',
            'DocumentType',
            $documentType->id,
            null,
            $documentType->toArray()
        );

        return response()->json([
            'data'    => new DocumentTypeResource($documentType),
            'message' => 'Tipo de documento creado correctamente.',
            'status'  => 201,
        ], 201);
    }

    /**
     * Show a single document type.
     *
     * @param  DocumentType $documentType
     * @return JsonResponse
     */
    public function show(DocumentType $documentType): JsonResponse
    {
        return response()->json([
            'data'    => new DocumentTypeResource($documentType),
            'message' => 'Tipo de documento obtenido correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Update an existing document type.
     *
     * @param  UpdateDocumentTypeRequest $request
     * @param  DocumentType              $documentType
     * @return JsonResponse
     */
    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): JsonResponse
    {
        $old = $documentType->toArray();
        $documentType->update($request->validated());

        AuditLog::log(
            'document_type.updated',
            'DocumentType',
            $documentType->id,
            $old,
            $documentType->fresh()->toArray()
        );

        return response()->json([
            'data'    => new DocumentTypeResource($documentType->fresh()),
            'message' => 'Tipo de documento actualizado correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Delete a document type (only if no associated submissions exist).
     *
     * @param  DocumentType $documentType
     * @return JsonResponse
     */
    public function destroy(DocumentType $documentType): JsonResponse
    {
        if ($documentType->submissions()->exists()) {
            return response()->json([
                'error'   => 'has_submissions',
                'message' => 'No se puede eliminar este tipo de documento porque tiene envíos asociados.',
                'status'  => 422,
            ], 422);
        }

        AuditLog::log(
            'document_type.deleted',
            'DocumentType',
            $documentType->id,
            $documentType->toArray(),
            null
        );

        $documentType->delete();

        return response()->json([
            'message' => 'Tipo de documento eliminado correctamente.',
            'status'  => 200,
        ]);
    }
}

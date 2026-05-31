<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\AuditLog;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * List all subjects, filterable by program_id.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::with('program');

        if ($request->filled('program_id')) {
            $query->byProgram((int) $request->program_id);
        }

        $subjects = $query->orderBy('name')->get();

        return response()->json([
            'data'    => SubjectResource::collection($subjects),
            'message' => 'Asignaturas obtenidas correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Create a new subject.
     *
     * @param  StoreSubjectRequest $request
     * @return JsonResponse
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = Subject::create($request->validated());

        AuditLog::log('subject.created', 'Subject', $subject->id, null, $subject->toArray());

        return response()->json([
            'data'    => new SubjectResource($subject->load('program')),
            'message' => 'Asignatura creada correctamente.',
            'status'  => 201,
        ], 201);
    }

    /**
     * Show a single subject.
     *
     * @param  Subject $subject
     * @return JsonResponse
     */
    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'data'    => new SubjectResource($subject->load('program')),
            'message' => 'Asignatura obtenida correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Update an existing subject.
     *
     * @param  UpdateSubjectRequest $request
     * @param  Subject              $subject
     * @return JsonResponse
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $old = $subject->toArray();
        $subject->update($request->validated());

        AuditLog::log('subject.updated', 'Subject', $subject->id, $old, $subject->fresh()->toArray());

        return response()->json([
            'data'    => new SubjectResource($subject->fresh()->load('program')),
            'message' => 'Asignatura actualizada correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Delete a subject.
     *
     * @param  Subject $subject
     * @return JsonResponse
     */
    public function destroy(Subject $subject): JsonResponse
    {
        AuditLog::log('subject.deleted', 'Subject', $subject->id, $subject->toArray(), null);

        $subject->delete();

        return response()->json([
            'message' => 'Asignatura eliminada correctamente.',
            'status'  => 200,
        ]);
    }
}

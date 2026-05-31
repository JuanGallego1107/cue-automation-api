<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Http\Resources\PeriodResource;
use App\Models\AuditLog;
use App\Models\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PeriodController extends Controller
{
    /**
     * List all periods.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $periods = Period::orderByDesc('start_date')->get();

        return response()->json([
            'data'    => PeriodResource::collection($periods),
            'message' => 'Períodos obtenidos correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Create a new period.
     *
     * @param  StorePeriodRequest $request
     * @return JsonResponse
     */
    public function store(StorePeriodRequest $request): JsonResponse
    {
        $period = Period::create($request->validated());

        AuditLog::log('period.created', 'Period', $period->id, null, $period->toArray());

        return response()->json([
            'data'    => new PeriodResource($period),
            'message' => 'Período creado correctamente.',
            'status'  => 201,
        ], 201);
    }

    /**
     * Show a single period.
     *
     * @param  Period $period
     * @return JsonResponse
     */
    public function show(Period $period): JsonResponse
    {
        return response()->json([
            'data'    => new PeriodResource($period),
            'message' => 'Período obtenido correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Update an existing period.
     *
     * @param  UpdatePeriodRequest $request
     * @param  Period              $period
     * @return JsonResponse
     */
    public function update(UpdatePeriodRequest $request, Period $period): JsonResponse
    {
        $old = $period->toArray();
        $period->update($request->validated());

        AuditLog::log('period.updated', 'Period', $period->id, $old, $period->fresh()->toArray());

        return response()->json([
            'data'    => new PeriodResource($period->fresh()),
            'message' => 'Período actualizado correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Delete a period (only if no associated submissions exist).
     *
     * @param  Period $period
     * @return JsonResponse
     */
    public function destroy(Period $period): JsonResponse
    {
        if ($period->submissions()->exists()) {
            return response()->json([
                'error'   => 'has_submissions',
                'message' => 'No se puede eliminar este período porque tiene envíos de documentos asociados.',
                'status'  => 422,
            ], 422);
        }

        AuditLog::log('period.deleted', 'Period', $period->id, $period->toArray(), null);

        $period->delete();

        return response()->json([
            'message' => 'Período eliminado correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Activate a period and deactivate all others (only one active period at a time).
     *
     * @param  Period $period
     * @return JsonResponse
     */
    public function activate(Period $period): JsonResponse
    {
        DB::transaction(function () use ($period) {
            // Deactivate all periods
            Period::query()->update(['is_active' => 0]);

            // Activate the specified one
            $period->update(['is_active' => 1]);

            AuditLog::log('period.activated', 'Period', $period->id, null, ['is_active' => true]);
        });

        return response()->json([
            'data'    => new PeriodResource($period->fresh()),
            'message' => "El período '{$period->name}' ha sido activado.",
            'status'  => 200,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentSubmissionResource;
use App\Models\DocumentSubmission;
use App\Models\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return dashboard statistics for the authenticated user.
     *
     * - Coordinators see their own stats.
     * - Admins see global stats across all coordinators.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->role?->name === 'Administrador';

        // Base query scope
        $baseQuery = DocumentSubmission::query();
        if (!$isAdmin) {
            $baseQuery->where('user_id', $user->id);
        }

        // Total count
        $totalSubmissions = (clone $baseQuery)->count();

        // Counts grouped by status
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byStatus = [
            'pending'          => (int) ($statusCounts[SubmissionStatus::PENDING->value] ?? 0),
            'processing'       => (int) ($statusCounts[SubmissionStatus::PROCESSING->value] ?? 0),
            'pending_approval' => (int) ($statusCounts[SubmissionStatus::PENDING_APPROVAL->value] ?? 0),
            'approved'         => (int) ($statusCounts[SubmissionStatus::APPROVED->value] ?? 0),
            'issues_found'     => (int) ($statusCounts[SubmissionStatus::ISSUES_FOUND->value] ?? 0),
            'failed'           => (int) ($statusCounts[SubmissionStatus::FAILED->value] ?? 0),
        ];

        // Recent 5 submissions
        $recentSubmissions = (clone $baseQuery)
            ->with(['documentType', 'subject', 'period'])
            ->latest()
            ->limit(5)
            ->get();

        // Active period
        $activePeriod = Period::where('is_active', true)->first();

        // Has active review (always useful for coordinator UI)
        $hasActiveReview = $isAdmin
            ? false
            : DocumentSubmission::hasActiveReview($user->id);

        return response()->json([
            'data' => [
                'total_submissions'  => $totalSubmissions,
                'by_status'          => $byStatus,
                'recent_submissions' => DocumentSubmissionResource::collection($recentSubmissions),
                'active_period'      => $activePeriod ? [
                    'id'         => $activePeriod->id,
                    'name'       => $activePeriod->name,
                    'start_date' => $activePeriod->start_date?->toDateString(),
                    'end_date'   => $activePeriod->end_date?->toDateString(),
                ] : null,
                'has_active_review'  => $hasActiveReview,
            ],
            'message' => 'Dashboard obtenido correctamente.',
            'status'  => 200,
        ]);
    }
}

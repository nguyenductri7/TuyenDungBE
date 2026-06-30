<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReEngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReEngagementController extends Controller
{
    public function __construct(private readonly ReEngagementService $reEngagementService)
    {
    }

    public function insights(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->get('similar_limit', 6), 1), 12);

        return response()->json([
            'success' => true,
            'data' => $this->reEngagementService->buildInsights($request->user(), $limit),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAiUsageController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $days = min(max((int) $request->get('days', 30), 1), 180);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $baseQuery = AiUsageLog::query()->whereBetween('created_at', [$from, $to]);
        $total = (clone $baseQuery)->count();
        $success = (clone $baseQuery)->where('status', AiUsageLog::STATUS_SUCCESS)->count();
        $errors = (clone $baseQuery)->where('status', AiUsageLog::STATUS_ERROR)->count();
        $fallbacks = (clone $baseQuery)->where('used_fallback', true)->count();
        $avgDuration = (clone $baseQuery)->whereNotNull('duration_ms')->avg('duration_ms');

        $featureStats = (clone $baseQuery)
            ->select('feature')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count", [AiUsageLog::STATUS_SUCCESS])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as error_count", [AiUsageLog::STATUS_ERROR])
            ->selectRaw('SUM(CASE WHEN used_fallback = 1 THEN 1 ELSE 0 END) as fallback_count')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->selectRaw('MAX(duration_ms) as max_duration_ms')
            ->groupBy('feature')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn (AiUsageLog $row) => [
                'feature' => $row->feature,
                'total' => (int) $row->total,
                'success_count' => (int) $row->success_count,
                'error_count' => (int) $row->error_count,
                'fallback_count' => (int) $row->fallback_count,
                'avg_duration_ms' => $row->avg_duration_ms !== null ? round((float) $row->avg_duration_ms) : null,
                'max_duration_ms' => $row->max_duration_ms !== null ? (int) $row->max_duration_ms : null,
                'success_rate' => $row->total > 0 ? round(((int) $row->success_count / (int) $row->total) * 100, 1) : 0,
            ]);

        $dailyRows = (clone $baseQuery)
            ->selectRaw($this->dateSelectExpression() . ' as date_key')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as error_count", [AiUsageLog::STATUS_ERROR])
            ->selectRaw('SUM(CASE WHEN used_fallback = 1 THEN 1 ELSE 0 END) as fallback_count')
            ->groupBy('date_key')
            ->orderBy('date_key')
            ->get()
            ->keyBy('date_key');

        $dailyTrend = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($from, $dailyRows) {
                $date = $from->copy()->addDays($offset)->toDateString();
                $row = $dailyRows->get($date);

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d/m'),
                    'total' => (int) ($row->total ?? 0),
                    'error_count' => (int) ($row->error_count ?? 0),
                    'fallback_count' => (int) ($row->fallback_count ?? 0),
                ];
            })
            ->values();

        $slowest = (clone $baseQuery)
            ->with(['user:id,ho_ten,email,vai_tro', 'company:id,ten_cong_ty,email'])
            ->whereNotNull('duration_ms')
            ->orderByDesc('duration_ms')
            ->limit(8)
            ->get()
            ->map(fn (AiUsageLog $log) => $this->mapLog($log));

        $recentIssues = (clone $baseQuery)
            ->with(['user:id,ho_ten,email,vai_tro', 'company:id,ten_cong_ty,email'])
            ->where(function (Builder $query) {
                $query->where('status', AiUsageLog::STATUS_ERROR)
                    ->orWhere('used_fallback', true);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AiUsageLog $log) => $this->mapLog($log));

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'days' => $days,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'summary' => [
                    'total_requests' => $total,
                    'success_count' => $success,
                    'error_count' => $errors,
                    'fallback_count' => $fallbacks,
                    'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0,
                    'error_rate' => $total > 0 ? round(($errors / $total) * 100, 1) : 0,
                    'fallback_rate' => $total > 0 ? round(($fallbacks / $total) * 100, 1) : 0,
                    'avg_duration_ms' => $avgDuration !== null ? round((float) $avgDuration) : null,
                ],
                'feature_stats' => $featureStats,
                'daily_trend' => $dailyTrend,
                'slowest_requests' => $slowest,
                'recent_issues' => $recentIssues,
            ],
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $query = AiUsageLog::query()
            ->with(['user:id,ho_ten,email,vai_tro', 'company:id,ten_cong_ty,email'])
            ->latest();

        if ($request->filled('feature')) {
            $query->where('feature', $request->input('feature'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('used_fallback')) {
            $query->where('used_fallback', $request->boolean('used_fallback'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('request_ref_type')) {
            $query->where('request_ref_type', $request->input('request_ref_type'));
        }

        if ($request->filled('request_ref_id')) {
            $query->where('request_ref_id', (int) $request->input('request_ref_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        $logs = $query->paginate(min((int) $request->get('per_page', 20), 100));
        $logs->setCollection($logs->getCollection()->map(fn (AiUsageLog $log) => $this->mapLog($log)));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function features(): JsonResponse
    {
        $features = AiUsageLog::query()
            ->select('feature')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('feature')
            ->orderBy('feature')
            ->get()
            ->map(fn (AiUsageLog $row) => [
                'feature' => $row->feature,
                'total' => (int) $row->total,
            ]);

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    private function mapLog(AiUsageLog $log): array
    {
        return [
            'id' => $log->id,
            'feature' => $log->feature,
            'endpoint' => $log->endpoint,
            'provider' => $log->provider,
            'model' => $log->model,
            'model_version' => $log->model_version,
            'status' => $log->status,
            'used_fallback' => (bool) $log->used_fallback,
            'duration_ms' => $log->duration_ms,
            'http_status' => $log->http_status,
            'error_message' => $log->error_message,
            'request_ref_type' => $log->request_ref_type,
            'request_ref_id' => $log->request_ref_id,
            'metadata_json' => $log->metadata_json,
            'created_at' => optional($log->created_at)->toISOString(),
            'user' => $log->user ? [
                'id' => $log->user->id,
                'ho_ten' => $log->user->ho_ten,
                'email' => $log->user->email,
                'vai_tro' => $log->user->vai_tro,
            ] : null,
            'company' => $log->company ? [
                'id' => $log->company->id,
                'ten_cong_ty' => $log->company->ten_cong_ty,
                'email' => $log->company->email,
            ] : null,
        ];
    }

    private function dateSelectExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "date(created_at)"
            : "DATE(created_at)";
    }
}

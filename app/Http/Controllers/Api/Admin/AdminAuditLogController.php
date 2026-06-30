<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\MapsAuditLogResponse;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    use MapsAuditLogResponse;

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with(['actor:id,ho_ten,email,vai_tro', 'company:id,ten_cong_ty,email'])
            ->latest();

        if ($request->filled('actor_id')) {
            $query->where('actor_id', (int) $request->input('actor_id'));
        }

        if ($request->filled('actor_query')) {
            $actorSearch = trim((string) $request->input('actor_query'));

            $query->where(function ($subQuery) use ($actorSearch) {
                if (ctype_digit($actorSearch)) {
                    $subQuery->orWhere('actor_id', (int) $actorSearch);
                }

                $subQuery->orWhereHas('actor', function ($actorQuery) use ($actorSearch) {
                    $actorQuery
                        ->where('ho_ten', 'like', "%{$actorSearch}%")
                        ->orWhere('email', 'like', "%{$actorSearch}%");
                });
            });
        }

        if ($request->filled('actor_role')) {
            $query->where('actor_role', $request->input('actor_role'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('scope')) {
            $query->where('metadata_json->scope', $request->input('scope'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        $logs = $query->paginate(min((int) $request->get('per_page', 20), 100));
        $logs->setCollection(
            $logs->getCollection()->map(fn (AuditLog $log) => $this->mapAuditLog($log))
        );

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function mapNotification(AppNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->loai,
            'title' => $notification->tieu_de,
            'message' => $notification->noi_dung,
            'to' => $notification->duong_dan,
            'data' => $notification->du_lieu_bo_sung ?: null,
            'read_at' => optional($notification->da_doc_luc)?->toISOString(),
            'created_at' => optional($notification->created_at)?->toISOString(),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()
            ->where('nguoi_dung_id', $request->user()->id)
            ->latest();

        if ($request->filled('type')) {
            $query->where('loai', $request->input('type'));
        }

        if ($request->boolean('unread_only')) {
            $query->whereNull('da_doc_luc');
        }

        $notifications = $query->paginate(min((int) $request->get('per_page', 15), 100));
        $notifications->setCollection(
            $notifications->getCollection()->map(fn (AppNotification $notification) => $this->mapNotification($notification))
        );

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $notifications,
                'unread_count' => AppNotification::where('nguoi_dung_id', $request->user()->id)
                    ->whereNull('da_doc_luc')
                    ->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => AppNotification::where('nguoi_dung_id', $request->user()->id)
                    ->whereNull('da_doc_luc')
                    ->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = AppNotification::where('nguoi_dung_id', $request->user()->id)->findOrFail($id);

        if (!$notification->da_doc_luc) {
            $notification->forceFill(['da_doc_luc' => now()])->save();
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapNotification($notification->fresh()),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        AppNotification::where('nguoi_dung_id', $request->user()->id)
            ->whereNull('da_doc_luc')
            ->update(['da_doc_luc' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LapehNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = LapehNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($n) => $this->payload($n));

        return response()->json(['notifications' => $notifications]);
    }

    public function markRead(Request $request, LapehNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['notification' => $this->payload($notification->fresh())]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        LapehNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked read.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = LapehNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    protected function payload(LapehNotification $n): array
    {
        return [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'read' => $n->isRead(),
            'created_at' => $n->created_at,
        ];
    }
}

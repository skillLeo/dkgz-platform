<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Formatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Portal/Benachrichtigungen', [
            'notifications' => $notifications->through(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'created_at_label' => Formatter::dateTime($n->created_at),
            ]),
        ]);
    }

    /**
     * The polling endpoint. Deliberately tiny — a count and the newest five —
     * because it is hit every 45 seconds by every open tab and the host is
     * shared. No websockets are available.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'items' => $user->unreadNotifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->data['title'] ?? 'Neue Benachrichtigung',
                    'body' => $n->data['body'] ?? null,
                    'url' => $n->data['url'] ?? null,
                    'created_at_label' => Formatter::dateTime($n->created_at),
                ]),
        ]);
    }

    public function markRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}

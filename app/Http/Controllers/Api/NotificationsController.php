<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    // Server-Sent Events (SSE) stream for realtime notifications
    public function stream(Request $request)
    {
        // Basic keep-alive SSE stream. In production, push real events from DB/queue.
        @set_time_limit(0);
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', 0);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        // Send an initial ping so client marks as connected
        echo ": connected\n\n"; @ob_flush(); @flush();

        $userId = $request->query('userId');
        $count = 0;
        // Simple loop to send periodic pings; exit after a few minutes to avoid runaway in dev
        while ($count < 120) { // ~10 minutes at 5s interval
            $payload = json_encode([
                'type' => 'ping',
                'ts' => now()->toIso8601String(),
                'userId' => $userId,
            ]);
            echo "event: ping\n";
            echo "data: {$payload}\n\n";
            @ob_flush(); @flush();
            if (connection_aborted()) { break; }
            $count++;
            sleep(5);
        }
        return response()->noContent();
    }

    // Accept and store FCM token (no-op storage for now)
    public function saveFcmToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'deviceInfo' => 'nullable',
        ]);

        // In a real deployment, persist this to a table like user_devices or notification_tokens
        // For now, just acknowledge
        return response()->json(['message' => 'FCM token received']);
    }
}

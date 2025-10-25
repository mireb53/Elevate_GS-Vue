<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('userId') ?: $request->header('x-user-id');
        if (!$userId) {
            // Still return empty events to avoid hard failures on landing
            return response()->json(['events' => []]);
        }

        // Upcoming classwork due dates for classes the user joined
        $rows = DB::table('classwork_items as ci')
            ->join('joined_classes as jc', 'jc.class_id', '=', 'ci.class_id')
            ->join('classes as c', 'c.class_id', '=', 'ci.class_id')
            ->select([
                'ci.id',
                'ci.title',
                'ci.type',
                'ci.due_at as start',
                'c.class_name as className',
            ])
            ->where('jc.user_id', $userId)
            ->whereNotNull('ci.due_at')
            ->orderBy('ci.due_at', 'asc')
            ->limit(25)
            ->get();

        return response()->json(['events' => $rows]);
    }
}

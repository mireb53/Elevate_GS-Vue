<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DebugController extends Controller
{
    public function authInspect(Request $request)
    {
        $userId = $request->header('x-user-id') ?: $request->query('userId');
        $exists = false;
        $user = null;
        if ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            $exists = (bool) $user;
        }
        return response()->json(['exists' => $exists, 'user' => $user]);
    }

    public function adminSelf(Request $request)
    {
        $userId = $request->query('userId');
        if (!$userId) return response()->json(['message' => 'Missing userId'], 400);
        $user = DB::table('users')->where('id', $userId)->first();
        if ($user && ($user->role ?? 'student') === 'admin') {
            return response()->json(['ok' => true, 'user' => $user]);
        }
        return response()->json(['ok' => false, 'message' => 'Not admin'], 403);
    }

    public function seedAdmin(Request $request)
    {
        // Dev helper: create a default admin if not present
        $email = 'admin@elevategs.com';
        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing) {
            return response()->json(['id' => $existing->id, 'email' => $existing->email, 'already' => true]);
        }
        $id = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('admin123'),
            'password_hash' => Hash::make('admin123'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['id' => $id, 'email' => $email]);
    }

    public function adminStats(Request $request)
    {
        // Minimal placeholder stats until full schema is in place
        return response()->json([
            'students' => 0,
            'instructors' => 0,
            'coursesActive' => 0,
            'coursesPending' => 0,
            'coursesArchived' => 0,
        ]);
    }

    public function adminNotifications(Request $request)
    {
        return response()->json([
            'pendingCoursesCount' => 0,
            'pendingCourses' => [],
        ]);
    }

    public function adminCourses(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $pageSize = max(1, min(50, (int) $request->query('pageSize', 10)));
        return response()->json([
            'items' => [],
            'total' => 0,
            'page' => $page,
            'pageSize' => $pageSize,
            'counts' => [ 'all' => 0, 'active' => 0, 'pending' => 0, 'archived' => 0 ],
        ]);
    }
}

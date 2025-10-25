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
        $status = strtolower((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));

        $qb = DB::table('classes as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->select(
                'c.class_id', 'c.class_name', 'c.program', 'c.section', 'c.subject_code', 'c.course_name', 'c.status', 'c.created_at',
                DB::raw("COALESCE(u.name, CONCAT(COALESCE(u.first_name, ''),' ',COALESCE(u.last_name, ''))) as teacher_display")
            );
        if (in_array($status, ['active','pending','archived'])) {
            $qb->where('c.status', $status);
        }
        if ($search !== '') {
            $qb->where(function($q) use ($search){
                $q->where('c.class_name', 'like', "%$search%")
                  ->orWhere('c.program', 'like', "%$search%")
                  ->orWhere('c.subject_code', 'like', "%$search%");
            });
        }

        $total = (clone $qb)->count();
        $rows = $qb->orderBy('c.created_at','desc')->offset(($page-1)*$pageSize)->limit($pageSize)->get();

        $active = DB::table('classes')->where('status','active')->count();
        $pending = DB::table('classes')->where('status','pending')->count();
        $archived = DB::table('classes')->where('status','archived')->count();

        $data = $rows->map(function($r){
            return [
                'id' => $r->class_id,
                'name' => $r->class_name,
                'class_name' => $r->class_name,
                'teacher' => $r->teacher_display,
                'teacher_display' => $r->teacher_display,
                'studentCount' => 0,
                'createdAt' => $r->created_at,
                'status' => $r->status,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'active' => $active,
                'pending' => $pending,
                'archived' => $archived,
            ]
        ]);
    }

    public function adminCoursesUpdate(Request $request, $id)
    {
        $status = strtolower((string) $request->input('status'));
        if (!in_array($status, ['active','archived','pending'])) {
            return response()->json(['message' => 'Invalid status'], 422);
        }
        $exists = DB::table('classes')->where('class_id', $id)->exists();
        if (!$exists) return response()->json(['message' => 'Not found'], 404);
        DB::table('classes')->where('class_id', $id)->update(['status' => $status, 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function adminCoursesDestroy(Request $request, $id)
    {
        DB::table('class_instructors')->where('class_id', $id)->delete();
        $deleted = DB::table('classes')->where('class_id', $id)->delete();
        if (!$deleted) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['ok' => true]);
    }
}

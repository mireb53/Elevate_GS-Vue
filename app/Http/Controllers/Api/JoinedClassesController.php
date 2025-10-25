<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JoinedClassesController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('userId') ?: $request->header('x-user-id');
        if (!$userId) return response()->json([]);

        $rows = DB::table('joined_classes as jc')
            ->join('classes as c', 'c.class_id', '=', 'jc.class_id')
            ->select(
                'c.class_id', 'c.program', 'c.class_name', 'c.section', 'c.subject_code', 'c.course_name', 'c.description', 'c.status', 'c.class_code',
                'jc.user_id'
            )
            ->where('jc.user_id', $userId)
            ->orderBy('c.created_at','desc')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        // Accept userId from body or legacy header x-user-id
        $userId = $request->input('userId') ?: (int)($request->header('x-user-id') ?: 0);
        $request->merge(['userId' => $userId]);
        $data = $request->validate([
            'userId' => 'required|integer|min:1',
            'classCode' => 'required|string|max:50',
        ]);

        $class = DB::table('classes')->where('class_code', $data['classCode'])->first();
        if (!$class) {
            return response()->json(['message' => 'Class code not found'], 404);
        }

        // Prevent owner joining as student; but allow if you want
        if ((int)$class->user_id === (int)$data['userId']) {
            return response()->json(['message' => 'You are the owner of this class'], 409);
        }

        // Insert membership if not exists
        $exists = DB::table('joined_classes')
            ->where('user_id', $data['userId'])
            ->where('class_id', $class->class_id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Already joined'], 200);
        }

        DB::table('joined_classes')->insert([
            'user_id' => $data['userId'],
            'class_id' => $class->class_id,
            'program' => $class->program,
            'status' => 'pending',
            'joined_at' => now(),
        ]);
        // Return legacy-like payload including class object
        return response()->json([
            'message' => 'Joined class successfully',
            'classId' => $class->class_id,
            'class' => $class,
        ]);
    }

    public function destroy(Request $request, $classId)
    {
        $userId = $request->header('x-user-id') ?: $request->query('userId');
        if (!$userId) {
            return response()->json(['message' => 'Missing user id'], 400);
        }

        $deleted = DB::table('joined_classes')
            ->where('class_id', $classId)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Left class successfully']);
        }
        return response()->json(['message' => 'Not found or already left'], 404);
    }
}

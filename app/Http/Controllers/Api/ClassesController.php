<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassesController extends Controller
{
    public function myClasses(Request $request)
    {
        $userId = $request->query('userId');
        if (!$userId) {
            return response()->json([], 200);
        }

        // Classes where the user is the owner (classes.user_id) OR is an instructor in class_instructors
        $owner = DB::table('classes')
            ->select('class_id','program','class_name','section','subject_code','course_name','description','status','class_code','user_id')
            ->where('user_id', $userId);

        $instructor = DB::table('classes as c')
            ->join('class_instructors as ci', 'ci.class_id', '=', 'c.class_id')
            ->select('c.class_id','c.program','c.class_name','c.section','c.subject_code','c.course_name','c.description','c.status','c.class_code','c.user_id')
            ->where('ci.user_id', $userId);

        $rows = $owner->unionAll($instructor)->distinct()->get();

        return response()->json($rows);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'userId' => 'nullable|integer', // may come via header x-user-id
            'program' => 'nullable|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'className' => 'nullable|string|max:255', // legacy key
            'section' => 'required|string|max:100',
            'subject_code' => 'required|string|max:50',
            'course_name' => 'nullable|string|max:255',
            'courseName' => 'nullable|string|max:255', // legacy key
            'description' => 'nullable|string',
            'classCode' => 'nullable|string|max:50',
            'schoolYear' => 'nullable|string|max:20',
            'courseType' => 'nullable|string|max:50',
            'academicYear' => 'nullable|string|max:50',
        ]);

        // Source userId from header if not provided in body
        $userId = $data['userId'] ?? (int)($request->header('x-user-id') ?: 0);
        if (!$userId) {
            return response()->json(['message' => 'Missing user id'], 422);
        }

        // Decide display name; prefer explicit className/courseName/program
        $displayName = $data['class_name']
            ?? $data['className']
            ?? $data['course_name']
            ?? $data['courseName']
            ?? $data['program']
            ?? 'Untitled Class';

        // Use provided classCode if unique; else generate one
        $classCode = null;
        if (!empty($data['classCode'])) {
            $exists = DB::table('classes')->where('class_code', $data['classCode'])->exists();
            if (!$exists) {
                $classCode = $data['classCode'];
            }
        }
        if (!$classCode) {
            for ($i=0; $i<5; $i++) {
                $candidate = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $exists = DB::table('classes')->where('class_code', $candidate)->exists();
                if (!$exists) { $classCode = $candidate; break; }
            }
            if (!$classCode) {
                return response()->json(['message' => 'Failed to generate class code. Try again.'], 500);
            }
        }

        $payload = [
            'program' => $data['program'] ?? null,
            'class_name' => $displayName,
            'section' => $data['section'],
            'subject_code' => $data['subject_code'],
            'course_name' => $data['course_name'] ?? $data['courseName'] ?? null,
            'description' => $data['description'] ?? null,
            'user_id' => $userId,
            'status' => 'active',
            'class_code' => $classCode,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $classId = DB::table('classes')->insertGetId($payload);

        // Optionally add owner into class_instructors as owner
        if (DB::getSchemaBuilder()->hasTable('class_instructors')) {
            try {
                DB::table('class_instructors')->insert([
                    'class_id' => $classId,
                    'user_id' => $userId,
                    'role_in_class' => 'owner',
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // ignore unique violations
            }
        }

        $row = DB::table('classes')->where('class_id', $classId)->first();
        return response()->json(['message' => 'Class created', 'classId' => $classId, 'class' => $row], 201);
    }
}

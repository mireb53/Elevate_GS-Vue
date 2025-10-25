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

        // Use UNION (distinct) to avoid duplicates when user is both owner and instructor
        $rows = $owner->union($instructor)->get();

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
            // New classes require admin approval and start as pending
            'status' => 'pending',
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
        return response()->json(['message' => 'Class created', 'status' => $row->status ?? 'pending', 'classId' => $classId, 'class' => $row], 201);
    }

    // GET /api/classes/{id}
    public function show(Request $request, $id)
    {
        $row = DB::table('classes')->where('class_id', $id)->first();
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    // GET /api/classes/{id}/classwork
    public function classwork(Request $request, $id)
    {
        // Minimal placeholder: return empty array if no classwork table
        if (!DB::getSchemaBuilder()->hasTable('classwork')) {
            return response()->json([]);
        }
        $rows = DB::table('classwork')->where('class_id', $id)->orderBy('created_at','desc')->get();
        return response()->json($rows);
    }

    // PUT /api/classwork/{id} - minimal update for title/description/due_at
    public function updateClasswork(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) {
            return response()->json(['message' => 'Classwork table not found'], 404);
        }
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);
        $exists = DB::table('classwork')->where('id', $id)->exists();
        if (!$exists) return response()->json(['message' => 'Not found'], 404);
        $payload = [];
        if (array_key_exists('title', $data)) $payload['title'] = $data['title'];
        if (array_key_exists('description', $data)) $payload['description'] = $data['description'];
        if (array_key_exists('due_at', $data)) $payload['due_at'] = $data['due_at'];
        if (!empty($payload)) $payload['updated_at'] = now();
        DB::table('classwork')->where('id', $id)->update($payload);
        $row = DB::table('classwork')->where('id', $id)->first();
        return response()->json(['ok' => true, 'classwork' => $row]);
    }

    // DELETE /api/classwork/{id}
    public function deleteClasswork(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) {
            return response()->json(['message' => 'Classwork table not found'], 404);
        }
        $deleted = DB::table('classwork')->where('id', $id)->delete();
        return response()->json(['ok' => (bool)$deleted]);
    }

    // GET /api/classes/{id}/people
    public function people(Request $request, $id)
    {
        $class = DB::table('classes')->where('class_id', $id)->first();
        if (!$class) return response()->json(['instructors'=>[], 'students'=>[]]);

        // Instructor(s): owner + class_instructors
        $instructors = [];
        if ($class->user_id) {
            $owner = DB::table('users')->where('id', $class->user_id)->first();
            if ($owner) $instructors[] = $owner;
        }
        if (DB::getSchemaBuilder()->hasTable('class_instructors')) {
            $ins = DB::table('class_instructors as ci')->join('users as u','u.id','=','ci.user_id')
                ->select('u.*','ci.role_in_class')->where('ci.class_id',$id)->get();
            foreach ($ins as $i) $instructors[] = $i;
        }

        // Students: from joined_classes
        $students = [];
        if (DB::getSchemaBuilder()->hasTable('joined_classes')) {
            $rows = DB::table('joined_classes as jc')->join('users as u','u.id','=','jc.user_id')
                ->select('u.*','jc.joined_at')->where('jc.class_id',$id)->get();
            foreach ($rows as $s) $students[] = $s;
        }

        return response()->json(['instructors' => $instructors, 'students' => $students]);
    }

    // GET /api/classes/{id}/records
    public function records(Request $request, $id)
    {
        // If there's a class_records table, return rows; otherwise return empty
        if (!DB::getSchemaBuilder()->hasTable('class_records')) return response()->json([]);
        $rows = DB::table('class_records')->where('class_id',$id)->orderBy('created_at','desc')->get();
        return response()->json($rows);
    }

    // GET /api/classes/{id}/gradebook/final-grades
    public function finalGrades(Request $request, $id)
    {
        // Minimal implementation: attempt to read a gradebook table or synthesize from joined_classes
        if (DB::getSchemaBuilder()->hasTable('gradebook')) {
            $rows = DB::table('gradebook')->where('class_id',$id)->get();
            return response()->json(['grades' => $rows]);
        }

        // Fallback: return joined students with null grades
        $students = [];
        if (DB::getSchemaBuilder()->hasTable('joined_classes')) {
            $rows = DB::table('joined_classes as jc')->join('users as u','u.id','=','jc.user_id')
                ->select('u.id as student_id','u.first_name','u.last_name','u.email')
                ->where('jc.class_id',$id)->get();
            foreach ($rows as $r) {
                $students[] = [
                    'student_id' => $r->student_id,
                    'first_name' => $r->first_name ?? null,
                    'last_name' => $r->last_name ?? null,
                    'midterm_grade' => null,
                    'final_grade' => null,
                    'overall_grade' => null
                ];
            }
        }
        return response()->json(['grades' => $students]);
    }

    // GET /api/classes/{id}/gradesheet
    public function gradesheet(Request $request, $id)
    {
        // Provide a simple JSON gradesheet for the Excel preview modal
        $class = DB::table('classes')->where('class_id', $id)->first();
        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // If a gradebook table exists, join with users; else synthesize from joined_classes
        $students = [];
        if (DB::getSchemaBuilder()->hasTable('gradebook')) {
            $rows = DB::table('gradebook as g')
                ->leftJoin('users as u', 'u.id', '=', 'g.student_id')
                ->select(
                    'g.student_id',
                    DB::raw('COALESCE(u.first_name, "") as first_name'),
                    DB::raw('COALESCE(u.last_name, "") as last_name'),
                    DB::raw('COALESCE(u.email, "") as email'),
                    DB::raw('COALESCE(g.midterm_grade, NULL) as midterm_grade'),
                    DB::raw('COALESCE(g.final_grade, NULL) as final_grade'),
                    DB::raw('COALESCE(g.remarks, "") as remarks')
                )
                ->where('g.class_id', $id)
                ->get();
            foreach ($rows as $r) {
                $students[] = [
                    'id' => $r->student_id,
                    'first_name' => $r->first_name,
                    'last_name' => $r->last_name,
                    'email' => $r->email,
                    'midterm_grade' => $r->midterm_grade,
                    'final_grade' => $r->final_grade,
                    'remarks' => $r->remarks,
                ];
            }
        } else if (DB::getSchemaBuilder()->hasTable('joined_classes')) {
            $rows = DB::table('joined_classes as jc')
                ->leftJoin('users as u', 'u.id', '=', 'jc.user_id')
                ->select('u.id as student_id','u.first_name','u.last_name','u.email')
                ->where('jc.class_id', $id)
                ->get();
            foreach ($rows as $r) {
                $students[] = [
                    'id' => $r->student_id,
                    'first_name' => $r->first_name ?? '',
                    'last_name' => $r->last_name ?? '',
                    'email' => $r->email ?? '',
                    'midterm_grade' => null,
                    'final_grade' => null,
                    'remarks' => '',
                ];
            }
        }

        return response()->json([
            'students' => $students,
            'classProgram' => $class->program ?? ''
        ]);
    }
}

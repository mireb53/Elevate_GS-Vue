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

    // GET /api/classes/{id}/gradebook - get gradebook structure and data
    public function getGradebook(Request $request, $id)
    {
        // Check if gradebook_config table exists
        if (!DB::getSchemaBuilder()->hasTable('gradebook_config')) {
            return response()->json(['gradebook' => null]);
        }

        $config = DB::table('gradebook_config')->where('class_id', $id)->first();
        
        if (!$config) {
            return response()->json(['gradebook' => null]);
        }

        // Decode the JSON structure
        $gradebook = [
            'midtermPercentage' => $config->midterm_percentage ?? 50,
            'finalsPercentage' => $config->finals_percentage ?? 50,
            'midtermTables' => json_decode($config->midterm_tables ?? '[]', true),
            'finalsTables' => json_decode($config->finals_tables ?? '[]', true),
            'grades' => json_decode($config->grades ?? '{}', true),
        ];

        return response()->json(['gradebook' => $gradebook]);
    }

    // POST /api/classes/{id}/gradebook - save gradebook structure and data
    public function saveGradebook(Request $request, $id)
    {
        // Ensure gradebook_config table exists
        if (!DB::getSchemaBuilder()->hasTable('gradebook_config')) {
            // Create the table if it doesn't exist
            DB::statement('
                CREATE TABLE IF NOT EXISTS gradebook_config (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    class_id BIGINT UNSIGNED NOT NULL,
                    midterm_percentage INT DEFAULT 50,
                    finals_percentage INT DEFAULT 50,
                    midterm_tables LONGTEXT,
                    finals_tables LONGTEXT,
                    grades LONGTEXT,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    UNIQUE KEY unique_class (class_id)
                )
            ');
        }

        $data = [
            'class_id' => $id,
            'midterm_percentage' => $request->input('midtermPercentage', 50),
            'finals_percentage' => $request->input('finalsPercentage', 50),
            'midterm_tables' => json_encode($request->input('midtermTables', [])),
            'finals_tables' => json_encode($request->input('finalsTables', [])),
            'grades' => json_encode($request->input('grades', [])),
            'updated_at' => now(),
        ];

        // Check if record exists
        $exists = DB::table('gradebook_config')->where('class_id', $id)->exists();

        if ($exists) {
            DB::table('gradebook_config')->where('class_id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('gradebook_config')->insert($data);
        }

        return response()->json(['message' => 'Gradebook saved successfully']);
    }

    // GET /api/classes/{id}/grades/summary — minimal student summary
    public function gradeSummary(Request $request, $id)
    {
        $userId = (int)($request->header('x-user-id') ?: $request->query('userId') ?: 0);
        $items = [];
        $categories = [];
        $overall = ['earned' => 0, 'possible' => 0, 'percentRaw' => null, 'weightingApplied' => false];

        if (DB::getSchemaBuilder()->hasTable('classwork')) {
            $rows = DB::table('classwork')->where('class_id', $id)->orderBy('created_at', 'desc')->get();
            foreach ($rows as $cw) {
                // find student's submission if table exists
                $submission = null;
                if ($userId && DB::getSchemaBuilder()->hasTable('classwork_submissions')) {
                    $submission = DB::table('classwork_submissions')
                        ->where(['classwork_id' => $cw->id, 'user_id' => $userId])
                        ->first();
                }
                $score = null; $max = null; $percent = null; $status = 'Assigned'; $late = false; $submittedAt = null;
                if ($submission) {
                    $submittedAt = $submission->submitted_at ?: $submission->created_at;
                    if (!empty($submission->grade_json)) {
                        $g = json_decode($submission->grade_json, true);
                        $score = $g['score'] ?? null;
                        $max = $g['totalPoints'] ?? 100;
                        $percent = ($score !== null && $max) ? ($score / $max) * 100 : null;
                        $status = 'Graded';
                    } else {
                        $status = 'Submitted';
                    }
                } else if (!empty($cw->due_at) && strtotime($cw->due_at) < time()) {
                    $status = 'Missing';
                    $late = true;
                }

                // Normalize type groups similar to legacy
                $type = $cw->type ?: 'Other';
                $group = $type;
                if (strcasecmp($type, 'activity') === 0 || strcasecmp($type, 'assignment') === 0) $group = 'Performance Task';

                $items[] = [
                    'id' => $cw->id,
                    'title' => $cw->title,
                    'type' => $group,
                    'score' => $score,
                    'max' => $max,
                    'percent' => $percent,
                    'status' => $status,
                    'late' => (bool)$late,
                    'dueAt' => $cw->due_at,
                    'submittedAt' => $submittedAt,
                ];

                if ($score !== null) {
                    $overall['earned'] += (float)$score;
                    $overall['possible'] += (float)($max ?: 100);
                }
                // accumulate categories simple sum
                if (!isset($categories[$group])) $categories[$group] = ['earned' => 0, 'possible' => 0, 'percent' => null, 'weight' => null];
                if ($score !== null) {
                    $categories[$group]['earned'] += (float)$score;
                    $categories[$group]['possible'] += (float)($max ?: 100);
                }
            }
        }

        foreach ($categories as $k => $c) {
            $categories[$k]['percent'] = $c['possible'] > 0 ? ($c['earned'] / $c['possible']) * 100 : null;
        }
        if ($overall['possible'] > 0) $overall['percentRaw'] = ($overall['earned'] / $overall['possible']) * 100;

        return response()->json([
            'items' => $items,
            'categories' => $categories,
            'overall' => $overall,
        ]);
    }

    // GET /api/classes/{id}/grades/self — minimal state about visibility
    public function gradeSelf(Request $request, $id)
    {
        $userId = (int)($request->header('x-user-id') ?: $request->query('userId') ?: 0);
        // For now, always visible; in future this can depend on approvals
        return response()->json([
            'userId' => $userId ?: null,
            'midterm' => null,
            'tentativeFinal' => null,
            'final' => null,
            'finalVisible' => true,
            'finalRequested' => false,
        ]);
    }

    // POST /api/classes/{id}/final/request — accept and return visible=true to unlock
    public function finalRequest(Request $request, $id)
    {
        // In a fuller implementation, store request and require teacher approval.
        // For now, auto-approve for demo purposes.
        return response()->json(['ok' => true, 'approved' => true]);
    }

    // POST /api/classes/{id}/classwork (JSON body)
    public function createClasswork(Request $request, $classId)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) {
            return response()->json(['message' => 'Classwork table not found'], 404);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:50',
            'dueDate' => 'nullable|string', // ISO string from UI; store into due_at
            'quiz' => 'nullable',
            'rubric' => 'nullable',
        ]);
        $payload = [
            'class_id' => (int)$classId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'due_at' => !empty($data['dueDate']) ? date('Y-m-d H:i:s', strtotime($data['dueDate'])) : null,
            'rubric_json' => isset($data['rubric']) ? json_encode($data['rubric']) : null,
            'extra_json' => isset($data['quiz']) ? json_encode(['quiz' => $data['quiz']]) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $id = DB::table('classwork')->insertGetId($payload);
        return response()->json(['id' => $id] + $payload, 201);
    }

    // POST /api/classes/{id}/classwork/upload (multipart form)
    public function uploadClasswork(Request $request, $classId)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) {
            return response()->json(['message' => 'Classwork table not found'], 404);
        }
        // Accept same fields as JSON create
        $data = [
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'type' => $request->input('type') ?: 'Material',
            'dueDate' => $request->input('dueDate'),
        ];
        $payload = [
            'class_id' => (int)$classId,
            'title' => $data['title'] ?: 'Untitled',
            'description' => $data['description'],
            'type' => $data['type'],
            'due_at' => !empty($data['dueDate']) ? date('Y-m-d H:i:s', strtotime($data['dueDate'])) : null,
            'rubric_json' => $request->has('rubric') ? $request->input('rubric') : null,
            'extra_json' => $request->has('quiz') ? json_encode(['quiz' => json_decode($request->input('quiz'), true)]) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $id = DB::table('classwork')->insertGetId($payload);
        // Attachments: move files to public/uploads and store metadata; MERGE with existing extra_json (e.g., quiz)
        $files = $request->file('attachments');
        if ($files) {
            $filesMeta = [];
            foreach ((array)$files as $f) {
                try {
                    $uploadDir = public_path('uploads');
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                    $stored = $f->hashName();
                    $f->move($uploadDir, $stored);
                    $filesMeta[] = [
                        'originalName' => $f->getClientOriginalName(),
                        'storedName' => $stored,
                        'url' => '/uploads/' . $stored,
                    ];
                } catch (\Throwable $e) {
                    try {
                        $filesMeta[] = [
                            'originalName' => method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : 'file',
                            'storedName' => method_exists($f, 'hashName') ? $f->hashName() : null,
                            'url' => '',
                        ];
                    } catch (\Throwable $e2) { /* ignore */ }
                }
            }
            // Merge with existing extra_json (e.g., quiz)
            $existingExtra = [];
            try {
                if ($payload['extra_json']) { $existingExtra = json_decode($payload['extra_json'], true) ?: []; }
            } catch (\Throwable $e) { $existingExtra = []; }
            $existingExtra['materialFiles'] = array_merge($existingExtra['materialFiles'] ?? [], $filesMeta);
            DB::table('classwork')->where('id',$id)->update(['extra_json' => json_encode($existingExtra)]);
        }
        return response()->json(['id' => $id] + $payload, 201);
    }

    // POST /api/classwork/{id}/link-category — stub ok
    public function linkCategory(Request $request, $id)
    {
        return response()->json(['ok'=>true]);
    }

    // POST /api/classwork/{id}/sync-scores — stub ok
    public function syncScores(Request $request, $id)
    {
        return response()->json(['ok'=>true]);
    }

    // GET /api/classwork/{id}/submissions — return empty list by default
    public function listSubmissions(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork_submissions')) {
            return response()->json([]);
        }
        // Build query with schema-aware ordering
        $query = DB::table('classwork_submissions')->where('classwork_id', $id);
        try {
            $hasSubmittedAt = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'submitted_at');
            $hasCreatedAt = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'created_at');
            if ($hasSubmittedAt) {
                $query = $query->orderBy('submitted_at', 'desc');
            } else if ($hasCreatedAt) {
                $query = $query->orderBy('created_at', 'desc');
            }
        } catch (\Throwable $e) { /* ignore ordering if schema introspection fails */ }
        $rows = $query->get();
        // normalize
        $out = [];
        foreach ($rows as $r) {
            // decode files JSON or fallback to extra_json->files
            $files = [];
            try {
                if (isset($r->files_json) && $r->files_json) {
                    $files = json_decode($r->files_json, true) ?: [];
                } else if (isset($r->extra_json) && $r->extra_json) {
                    $extra = json_decode($r->extra_json, true) ?: [];
                    if (isset($extra['files']) && is_array($extra['files'])) {
                        $files = $extra['files'];
                    }
                }
            } catch (\Throwable $e) { $files = []; }

            // decode grade when present
            $grade = null;
            try {
                if (isset($r->grade_json) && $r->grade_json) {
                    $grade = json_decode($r->grade_json, true) ?: null;
                }
            } catch (\Throwable $e) { $grade = null; }

            $out[] = [
                'id' => $r->id,
                'userId' => $r->user_id,
                'submittedAt' => $r->submitted_at ?? ($r->created_at ?? null),
                'files' => $files,
                'grade' => $grade,
            ];
        }
        return response()->json($out);
    }

    // GET /api/classwork/{id}/rubric — pull from classwork.rubric_json
    public function getRubric(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) return response()->json([]);
        $row = DB::table('classwork')->where('id',$id)->first();
        $rubric = $row && $row->rubric_json ? json_decode($row->rubric_json, true) : [];
        return response()->json(['rubric'=>$rubric]);
    }

    // POST /api/classwork/{classworkId}/submissions/{submissionId}/grade (or fallback)
    public function gradeSubmission(Request $request, $classworkId, $submissionId = null)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork_submissions')) {
            // accept but no-op
            return response()->json(['ok'=>true]);
        }
        $data = $request->validate(['userId'=>'nullable|integer','score'=>'required|numeric']);
        $grade = ['score' => $data['score'], 'totalPoints' => 100, 'gradedBy'=>'teacher'];
        if ($submissionId) {
            DB::table('classwork_submissions')->where('id',$submissionId)->update(['grade_json'=>json_encode($grade),'updated_at'=>now()]);
        } else if (!empty($data['userId'])) {
            DB::table('classwork_submissions')->where(['classwork_id'=>$classworkId,'user_id'=>$data['userId']])->update(['grade_json'=>json_encode($grade),'updated_at'=>now()]);
        }
        return response()->json(['ok'=>true]);
    }

    // GET /api/classwork/{id}
    public function getClasswork(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork')) return response()->json(['message'=>'Not found'], 404);
        $row = DB::table('classwork')->where('id',$id)->first();
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        // normalize: include quiz/rubric from JSON columns
        $extra = $row->extra_json ? json_decode($row->extra_json, true) : [];
        $rubric = $row->rubric_json ? json_decode($row->rubric_json, true) : null;
        $out = [
            'id' => $row->id,
            'class_id' => $row->class_id,
            'title' => $row->title,
            'type' => $row->type,
            'description' => $row->description,
            'due_at' => $row->due_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'rubric' => $rubric,
        ];
        if (isset($extra['quiz'])) $out['quiz'] = $extra['quiz'];
        if (isset($extra['materialFiles'])) $out['materialFiles'] = $extra['materialFiles'];
        if (isset($extra['materialFile'])) $out['materialFile'] = $extra['materialFile'];
        return response()->json($out);
    }

    // GET /api/classwork/{id}/submission/me
    public function getMySubmission(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork_submissions')) return response()->json(['submitted'=>false]);
        $userId = (int)($request->header('x-user-id') ?: $request->query('userId') ?: 0);
        if (!$userId) return response()->json(['submitted'=>false]);
        $row = DB::table('classwork_submissions')->where(['classwork_id'=>$id,'user_id'=>$userId])->first();
        if (!$row) return response()->json(['submitted'=>false]);
        return response()->json([
            'id' => $row->id,
            'submitted' => true,
            'submission_time' => $row->submitted_at ?: $row->created_at,
            'files' => $row->files_json ? json_decode($row->files_json, true) : [],
            'grade' => $row->grade_json ? json_decode($row->grade_json, true) : null,
            'answers' => $row->answers_json ? json_decode($row->answers_json, true) : null,
        ]);
    }

    // POST /api/classwork/{id}/submit
    public function submitClasswork(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork_submissions')) return response()->json(['message'=>'Submissions disabled'], 404);
        $userId = (int)($request->header('x-user-id') ?: $request->query('userId') ?: 0);
        if (!$userId) return response()->json(['message'=>'Missing user'], 422);

        $filesMeta = [];
        $files = $request->file('attachments');
        if ($files) {
            foreach ((array)$files as $f) {
                try {
                    // Ensure uploads directory exists
                    $uploadDir = public_path('uploads');
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                    // Generate unique stored name and move file
                    $stored = $f->hashName();
                    $f->move($uploadDir, $stored);
                    $filesMeta[] = [
                        'originalName' => $f->getClientOriginalName(),
                        'storedName' => $stored,
                        'url' => '/uploads/' . $stored,
                    ];
                } catch (\Throwable $e) {
                    // On failure, try minimal metadata without moving
                    try {
                        $filesMeta[] = [
                            'originalName' => method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : 'file',
                            'storedName' => method_exists($f, 'hashName') ? $f->hashName() : null,
                            'url' => '',
                        ];
                    } catch (\Throwable $e2) { /* ignore */ }
                }
            }
        }
        $answers = null;
        if ($request->has('answers')) {
            $raw = $request->input('answers');
            $answers = is_string($raw) ? json_decode($raw, true) : $raw;
        }
        // Build payload resiliently to different schema variants
        $payload = [
            'classwork_id' => (int)$id,
            'user_id' => $userId,
        ];
        try {
            $schema = DB::getSchemaBuilder();
            $hasFiles = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'files_json');
            $hasAnswers = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'answers_json');
            $hasExtra = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'extra_json');
            $hasGrade = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'grade_json');
            $hasSubmittedAt = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'submitted_at');
            $hasCreatedAt = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'created_at');
            $hasUpdatedAt = \Illuminate\Support\Facades\Schema::hasColumn('classwork_submissions', 'updated_at');

            if ($hasSubmittedAt) {
                $payload['submitted_at'] = now();
            }
            if ($hasGrade) {
                $payload['grade_json'] = null;
            }
            if ($hasCreatedAt) {
                $payload['created_at'] = now();
            }
            if ($hasUpdatedAt) {
                $payload['updated_at'] = now();
            }

            if ($hasFiles) {
                $payload['files_json'] = $filesMeta ? json_encode($filesMeta) : null;
            } else if ($hasExtra && $filesMeta) {
                // Fallback: store files under extra_json when files_json doesn't exist
                $payload['extra_json'] = json_encode(['files' => $filesMeta]);
            }
            if ($hasAnswers) {
                $payload['answers_json'] = $answers ? json_encode($answers) : null;
            } else if ($hasExtra && $answers) {
                // Merge into extra_json while preserving any files payload
                $existingExtra = isset($payload['extra_json']) ? json_decode($payload['extra_json'], true) : [];
                $existingExtra['answers'] = $answers;
                $payload['extra_json'] = json_encode($existingExtra);
            }
        } catch (\Throwable $e) {
            // If schema inspection fails, keep minimal payload; DB may ignore unknown columns
        }
        // Upsert: if a submission already exists, update it
        $existing = DB::table('classwork_submissions')->where(['classwork_id'=>$id,'user_id'=>$userId])->first();
        if ($existing) {
            DB::table('classwork_submissions')->where('id',$existing->id)->update($payload);
            $sid = $existing->id;
        } else {
            $sid = DB::table('classwork_submissions')->insertGetId($payload);
        }
        return response()->json(['ok'=>true,'id'=>$sid]);
    }

    // DELETE /api/submissions/{id}
    public function deleteSubmission(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('classwork_submissions')) return response()->json(['ok'=>true]);
        DB::table('classwork_submissions')->where('id',$id)->delete();
        return response()->json(['ok'=>true]);
    }
}

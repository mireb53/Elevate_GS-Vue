<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    private function loadYears(): array
    {
        try {
            if (Storage::exists('academic_years.json')) {
                $raw = Storage::get('academic_years.json');
                $data = json_decode($raw, true);
                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {}
        return [];
    }

    private function saveYears(array $years): void
    {
        Storage::put('academic_years.json', json_encode(array_values($years)));
    }

    public function active(Request $request)
    {
        // Prefer stored active record if available
        $years = $this->loadYears();
        foreach ($years as $y) {
            if (($y['status'] ?? 'inactive') === 'active') {
                return response()->json([
                    'id' => $y['id'] ?? null,
                    'year_name' => $y['year_name'] ?? null,
                    'status' => 'active',
                    'version' => $y['version'] ?? null,
                ]);
            }
        }
        // Fallback: compute based on current date
        $now = now();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        if ($month >= 6) { $start = $year; $end = $year + 1; } else { $start = $year - 1; $end = $year; }
        return response()->json([
            'id' => $start.$end,
            'year_name' => sprintf('%d-%d', $start, $end),
            'status' => 'active',
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->loadYears());
    }

    public function store(Request $request)
    {
        $request->validate([
            'yearName' => 'required|string',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx',
            'notes' => 'nullable|string',
            'setAsActive' => 'nullable|string',
        ]);

        $years = $this->loadYears();
        $yearName = $request->input('yearName');
        $notes = $request->input('notes', '');
        $setAsActive = filter_var($request->input('setAsActive'), FILTER_VALIDATE_BOOLEAN);

        // Compute version per same year
        $same = array_values(array_filter($years, fn($y) => ($y['year_name'] ?? '') === $yearName));
        $versionNumber = count($same) + 1;
        $version = 'v' . $versionNumber;

        // Store file in public disk
        $uploaded = $request->file('file');
        $origName = $uploaded->getClientOriginalName();
        $path = $uploaded->store('academic-years', 'public');
        $url = Storage::url($path);

        // Resolve uploader name
        $userId = $request->header('x-user-id') ?: $request->query('userId');
        $uploadedBy = 'Admin';
        if ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user) $uploadedBy = $user->name ?? ($user->first_name ?? 'Admin');
        }

        $id = uniqid('ay_', true);
        if ($setAsActive) {
            foreach ($years as &$y) { $y['status'] = 'inactive'; }
        }
        $record = [
            'id' => $id,
            'year_name' => $yearName,
            'version' => $version,
            'file_path' => $url,
            'file_name' => $origName,
            'notes' => $notes,
            'uploaded_by_name' => $uploadedBy,
            'created_at' => now()->toISOString(),
            'status' => $setAsActive ? 'active' : 'inactive',
        ];
        $years[] = $record;
        $this->saveYears($years);

        return response()->json(['message' => 'Academic year uploaded successfully.', 'id' => $id]);
    }

    public function activate(Request $request, $id)
    {
        $years = $this->loadYears();
        $found = false;
        foreach ($years as &$y) {
            if (($y['id'] ?? null) === $id) { $y['status'] = 'active'; $found = true; }
            else { $y['status'] = 'inactive'; }
        }
        if (!$found) return response()->json(['message' => 'Academic year not found'], 404);
        $this->saveYears($years);
        return response()->json(['message' => 'Active academic year updated.']);
    }

    public function destroy(Request $request, $id)
    {
        $years = $this->loadYears();
        $idx = null;
        foreach ($years as $i => $y) {
            if (($y['id'] ?? null) === $id) { $idx = $i; break; }
        }
        if ($idx === null) return response()->json(['message' => 'Academic year not found'], 404);
        // attempt to delete file if in public storage path
        $fileUrl = $years[$idx]['file_path'] ?? null;
        if ($fileUrl) {
            // Convert URL to path; Storage::url('path') returns '/storage/path'
            $prefix = '/storage/';
            if (str_starts_with($fileUrl, $prefix)) {
                $rel = substr($fileUrl, strlen($prefix));
                Storage::disk('public')->delete($rel);
            }
        }
        array_splice($years, $idx, 1);
        $this->saveYears($years);
        return response()->json(['message' => 'Academic year deleted successfully.']);
    }
}

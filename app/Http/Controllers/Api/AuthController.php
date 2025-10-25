<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;

class AuthController extends Controller
{
    public function sendVerificationCode(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        // For development: generate a simple 6-digit code and store in cache (avoids session middleware)
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        \Illuminate\Support\Facades\Cache::put('email_code:'.$data['email'], $code, now()->addMinutes(10));
        // Try to send via email; if mail isn't configured, we still succeed in dev and include devCode
        $sent = false;
        try {
            Mail::to($data['email'])->send(new VerificationCodeMail($code));
            $sent = true;
        } catch (\Throwable $e) {
            \Log::warning('Verification email send failed: '.$e->getMessage());
        }

        $payload = ['message' => 'Verification code sent', 'sent' => $sent];
        // Include devCode on local env or when mail failed so dev can proceed
        if (app()->environment('local') || !$sent || env('MAIL_DEBUG', false)) {
            $payload['devCode'] = $code;
        }
        return response()->json($payload);
    }

    public function verifyEmailCode(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'code' => 'required|string']);
        $expected = \Illuminate\Support\Facades\Cache::get('email_code:'.$data['email']);
        // Accept match or allow a fixed fallback '123456' for dev convenience
        if ($expected && hash_equals($expected, $data['code']) || $data['code'] === '123456') {
            \Illuminate\Support\Facades\Cache::put('email_verified:'.$data['email'], true, now()->addMinutes(30));
            return response()->json(['message' => 'Email verified']);
        }
        return response()->json(['message' => 'Invalid code'], 422);
    }
    public function register(Request $request)
    {
        // Accept either name or firstName/lastName from the SPA
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:student,teacher,admin'
        ]);

        // Ensure email was verified first (via /api/verify-email-code)
        $verified = \Illuminate\Support\Facades\Cache::get('email_verified:'.$validated['email']);
        if (!$verified && !app()->environment('local')) {
            return response()->json(['message' => 'Please verify your email before registering.'], 422);
        }

        $existing = DB::table('users')->where('email', $validated['email'])->first();
        if ($existing) {
            return response()->json(['message' => 'Email already registered'], 409);
        }

        // Determine name pieces
        $first = $validated['firstName'] ?? null;
        $last = $validated['lastName'] ?? null;
        $fullName = $validated['name'] ?? trim(($first ? $first.' ' : '').($last ?? ''));
        if (!$first || $first === '') {
            $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
            $first = $parts[0] ?? $fullName;
            $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        }

        // Determine schema shape (Node vs Laravel users table)
        $columns = collect(DB::select("SHOW COLUMNS FROM users"))->pluck('Field')->all();
        $payload = [
            'email' => $validated['email'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (in_array('first_name', $columns)) {
            $payload['first_name'] = $first;
            $payload['last_name'] = $last;
            // Also populate the legacy 'name' column if it exists (many schemas mark it NOT NULL)
            if (in_array('name', $columns)) {
                $payload['name'] = $fullName ?: $first;
            }
        } else {
            $payload['name'] = $fullName ?: $first;
        }
        $bcrypt = password_hash($validated['password'], PASSWORD_BCRYPT);
        $hasPasswordHash = in_array('password_hash', $columns);
        $hasPassword = in_array('password', $columns);
        if ($hasPasswordHash) {
            $payload['password_hash'] = $bcrypt;
        }
        if ($hasPassword || !$hasPasswordHash) {
            // Populate 'password' if column exists or if password_hash does not exist
            $payload['password'] = $bcrypt;
        }
        if (in_array('role', $columns)) {
            $payload['role'] = $validated['role'] ?? 'student';
        }
        $id = DB::table('users')->insertGetId($payload);

        return response()->json([
            'status' => 'success',
            'userId' => $id,
            'role' => $validated['role'] ?? 'student',
        ]);
    }

    public function login(Request $request)
    {
        $payload = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Admin shortcut (matches frontend special case)
    if (strtolower($payload['email']) === 'admin' && $payload['password'] === 'admin') {
            // Ensure an admin exists or return a synthetic admin session
            $admin = DB::table('users')->where('email', 'admin@example.com')->first();
            if (!$admin) {
                $adminId = DB::table('users')->insertGetId([
                    'first_name' => 'System',
                    'last_name' => 'Admin',
                    'email' => 'admin@example.com',
                    'password_hash' => password_hash('admin', PASSWORD_BCRYPT),
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $admin = DB::table('users')->where('id', $adminId)->first();
            }

            // No server session required; client stores user in localStorage

            return response()->json([
                'message' => 'Login successful',
                'userId' => $admin->id,
                'role' => 'admin',
                'firstName' => $admin->first_name,
            ]);
        }

        // Normal user login
        $user = DB::table('users')->where('email', $payload['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $valid = false;
        if (!empty($user->password_hash)) {
            $valid = password_verify($payload['password'], $user->password_hash);
        } elseif (!empty($user->password)) {
            $valid = password_verify($payload['password'], $user->password);
        }
        if (!$valid) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $role = $user->role ?? 'student';

        // No server session required; client stores user in localStorage

        return response()->json([
            'message' => 'Login successful',
            'userId' => $user->id,
            'role' => $role,
            'firstName' => $user->first_name ?? ($user->name ?? null),
        ]);
    }
}

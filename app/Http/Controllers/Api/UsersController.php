<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * Return a minimal user profile compatible with the legacy frontend.
     */
    public function show($id)
    {
        $user = DB::table('users')
            ->select(
                'id',
                'name',
                'first_name',
                'last_name',
                'email',
                'role',
                'google_picture',
                'profile_picture'
            )
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Compose a friendly display name similar to legacy behavior
        $displayName = $user->name;
        if (!$displayName) {
            $first = $user->first_name ?: '';
            $last = $user->last_name ?: '';
            $displayName = trim($first.' '.$last) ?: null;
        }

        return response()->json([
            'id' => (int)$user->id,
            'name' => $displayName,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role ?? 'student',
            'google_picture' => $user->google_picture,
            'profile_picture' => $user->profile_picture,
        ]);
    }
}

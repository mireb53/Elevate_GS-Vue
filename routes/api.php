<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\ClassesController;
use App\Http\Controllers\Api\JoinedClassesController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\UsersController;

// Laravel 11 withRouting(api: ...) does not auto-prefix; expose endpoints exactly at /api/* from web root
// We register them here without an extra 'api' prefix, because bootstrap/app.php maps this file under the /api path group.

    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/verify-email-code', [AuthController::class, 'verifyEmailCode']);

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index']);

    // Classes
    Route::get('/classes', [ClassesController::class, 'myClasses']);
    Route::post('/classes', [ClassesController::class, 'create']);

    // Joined classes
    Route::get('/joined-classes', [JoinedClassesController::class, 'index']);
    Route::post('/joined-classes', [JoinedClassesController::class, 'store']);
    // Legacy alias used by old frontend
    Route::post('/join-class', [JoinedClassesController::class, 'store']);
    Route::delete('/joined-classes/{classId}', [JoinedClassesController::class, 'destroy']);

    // Debug helpers to match legacy frontend expectations
    Route::get('/debug/auth-inspect', [DebugController::class, 'authInspect']);
    Route::get('/admin/self', [DebugController::class, 'adminSelf']);
    Route::post('/admin/seed-admin', [DebugController::class, 'seedAdmin']);
    Route::get('/admin/stats', [DebugController::class, 'adminStats']);
    Route::get('/admin/notifications', [DebugController::class, 'adminNotifications']);
    Route::get('/admin/courses', [DebugController::class, 'adminCourses']);

    // Minimal Academic Year endpoint to satisfy legacy CreateClass flow
    Route::get('/academic-years/active', [AcademicYearController::class, 'active']);
    // Admin Academic Year management
    Route::get('/admin/academic-years', [AcademicYearController::class, 'index']);
    Route::post('/admin/academic-years', [AcademicYearController::class, 'store']);
    Route::put('/admin/academic-years/{id}/activate', [AcademicYearController::class, 'activate']);
    Route::delete('/admin/academic-years/{id}', [AcademicYearController::class, 'destroy']);

    // Users
    Route::get('/users/{id}', [UsersController::class, 'show']);


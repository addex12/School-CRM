<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\Parent\ParentController;

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Admin Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('admin/users', AdminController::class);
    Route::post('admin/settings', [AdminController::class, 'updateSettings']);
    Route::apiResource('admin/surveys', AdminController::class);
});

// Student Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/surveys', [StudentController::class, 'index']);
    Route::post('student/surveys/{survey}/responses', [StudentController::class, 'submitResponse']);
});

// Teacher Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('teacher/surveys', [TeacherController::class, 'index']);
});

// Parent Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('parent/surveys', [ParentController::class, 'index']);
});

// Chat Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('chat/messages', [ChatController::class, 'index']);
    Route::post('chat/messages', [ChatController::class, 'store']);
});
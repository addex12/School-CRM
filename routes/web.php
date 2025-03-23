<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Student\SurveyController;
use App\Http\Controllers\Teacher\SurveyController as TeacherSurveyController;
use App\Http\Controllers\Parent\SurveyController as ParentSurveyController;
use App\Http\Controllers\ChatController;

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // Additional admin routes for managing users, settings, etc.
});

// Student Routes
Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/student/surveys', [SurveyController::class, 'index'])->name('student.surveys');
    Route::post('/student/surveys/{survey}/submit', [SurveyController::class, 'submit'])->name('student.surveys.submit');
});

// Teacher Routes
Route::middleware(['auth', 'teacher'])->group(function () {
    Route::get('/teacher/surveys', [TeacherSurveyController::class, 'index'])->name('teacher.surveys');
    Route::post('/teacher/surveys/{survey}/submit', [TeacherSurveyController::class, 'submit'])->name('teacher.surveys.submit');
});

// Parent Routes
Route::middleware(['auth', 'parent'])->group(function () {
    Route::get('/parent/surveys', [ParentSurveyController::class, 'index'])->name('parent.surveys');
    Route::post('/parent/surveys/{survey}/submit', [ParentSurveyController::class, 'submit'])->name('parent.surveys.submit');
});

// Chat Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    // Additional chat routes for sending and receiving messages
});
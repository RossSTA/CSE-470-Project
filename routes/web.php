<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::view('/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'email'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update')->middleware('throttle:6,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/profiles/{profile}', [StudentProfileController::class, 'show'])->name('profile.show');
    Route::middleware('role:client')->group(function () {
        Route::get('/client/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
        Route::post('/jobs/{job}/publish', [JobController::class, 'publish'])->name('jobs.publish');
        Route::post('/jobs/{job}/close', [JobController::class, 'close'])->name('jobs.close');
    });
    Route::get('/jobs', [JobController::class, 'browse'])->middleware('role:student')->name('jobs.browse');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
    Route::middleware('role:student')->group(function () {
        Route::get('/profiles/{profile}/edit', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profiles/{profile}', [StudentProfileController::class, 'update'])->name('profile.update');
        Route::post('/profiles/{profile}/skills', [StudentProfileController::class, 'skill'])->name('skills.store');
        Route::put('/profiles/{profile}/skills/{studentSkill}', [StudentProfileController::class, 'skill'])->name('skills.update');
        Route::delete('/profiles/{profile}/skills/{studentSkill}', [StudentProfileController::class, 'removeSkill'])->name('skills.destroy');
        Route::post('/profiles/{profile}/projects', [StudentProfileController::class, 'project'])->name('projects.store');
        Route::get('/projects/{portfolioItem}/edit', [StudentProfileController::class, 'editProject'])->name('projects.edit');
        Route::put('/projects/{portfolioItem}', [StudentProfileController::class, 'updateProject'])->name('projects.update');
        Route::delete('/projects/{portfolioItem}', [StudentProfileController::class, 'deleteProject'])->name('projects.destroy');
        Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verification', [VerificationController::class, 'store'])->name('verification.store')->middleware('throttle:5,1');
    });
    Route::get('/verification/{verification}/document', [VerificationController::class, 'document'])->name('verification.document');
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/verifications', [VerificationController::class, 'admin'])->name('admin.verifications');
        Route::get('/admin/verifications/{verification}', [VerificationController::class, 'show'])->name('admin.verification');
        Route::post('/admin/verifications/{verification}/review',[VerificationController::class, 'review'])->name('admin.review');
    });
});

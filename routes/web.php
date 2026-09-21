<?php

use App\Http\Controllers\Admin\ScheduleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules/generate', [ScheduleController::class, 'generate'])->name('schedules.generate');
    Route::post('/schedules/items/{item}/toggle-pin', [ScheduleController::class, 'togglePin'])->name('schedules.toggle-pin');
    Route::post('/schedules/{schedule}/publish', [ScheduleController::class, 'publish'])->name('schedules.publish');
});

Route::prefix('dosen')->name('dosen.')->group(function () {
    Route::get('/krs', [\App\Http\Controllers\Dosen\KrsApprovalController::class, 'index'])->name('krs.index');
    Route::post('/krs/{krs}/approve', [\App\Http\Controllers\Dosen\KrsApprovalController::class, 'approve'])->name('krs.approve');
    Route::post('/krs/{krs}/revise', [\App\Http\Controllers\Dosen\KrsApprovalController::class, 'revise'])->name('krs.revise');

    Route::get('/kelas/{kelas}/quizzes/create', [\App\Http\Controllers\Dosen\QuizManagementController::class, 'create'])->name('quizzes.create');
    Route::post('/kelas/{kelas}/quizzes', [\App\Http\Controllers\Dosen\QuizManagementController::class, 'store'])->name('quizzes.store');
});

Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/krs', [\App\Http\Controllers\Mahasiswa\KrsController::class, 'index'])->name('krs.index');
    Route::post('/krs', [\App\Http\Controllers\Mahasiswa\KrsController::class, 'store'])->name('krs.store');

    Route::get('/quizzes/{quiz}', [\App\Http\Controllers\Mahasiswa\QuizRunnerController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/submit', [\App\Http\Controllers\Mahasiswa\QuizRunnerController::class, 'submit'])->name('quizzes.submit');
});

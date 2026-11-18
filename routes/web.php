<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Object3dController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SensorSampleController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return auth()->user()->isTeacher()
        ? redirect()->route('admin.activities.index')
        : redirect()->route('play.index');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'teacher'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // acompanhamento da turma
        Route::get('activities/{activity}/report', [ReportController::class, 'activity'])->name('reports.activity');
        Route::get('activities/{activity}/report.csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('participations/{participation}', [ReportController::class, 'participation'])->name('reports.participation');
        Route::delete('activities/{activity}/participations', [ReportController::class, 'reset'])->name('reports.reset');

        Route::resource('activities', ActivityController::class);
        Route::resource('activities.questions', QuestionController::class)
            ->except(['index', 'show'])
            ->shallow();
        Route::resource('objects3d', Object3dController::class)->except('show');
    });


    Route::middleware('auth')
    ->prefix('play')
    ->name('play.')
    ->group(function () {
        Route::get('activities', [PlayController::class, 'index'])->name('index');
        Route::post('activities/{activity}/start', [PlayController::class, 'start'])->name('start');
        Route::get('participations/{participation}', [PlayController::class, 'show'])->name('show');
        Route::get('participations/{participation}/ar', [PlayController::class, 'ar'])->name('ar');
        Route::get('participations/{participation}/state', [PlayController::class, 'state'])->name('state');
        Route::get('participations/{participation}/question', [PlayController::class, 'question'])->name('question');
        Route::post('participations/{participation}/answer', [PlayController::class, 'answer'])->name('answer');
        Route::post('participations/{participation}/samples', [SensorSampleController::class, 'store'])->name('samples.store');
    });
    
require __DIR__.'/auth.php';

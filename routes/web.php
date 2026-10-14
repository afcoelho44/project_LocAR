<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Object3dController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\QuestionController;


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
    });
    
require __DIR__.'/auth.php';

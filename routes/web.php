<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackgroundJobDashboardController;

// Test route for background jobs
Route::get('/test-background-job', function () {
    runBackgroundJob(\App\Jobs\ExampleJob::class, 'handle', ['test-param-1', 'test-param-2']);
    return 'Background job dispatched. Check logs for results.';
});

// Background Jobs Dashboard Routes
Route::get('/background-jobs', [BackgroundJobDashboardController::class, 'index'])->name('background-jobs.index');
Route::get('/background-jobs/refresh', [BackgroundJobDashboardController::class, 'index'])->name('background-jobs.refresh');
Route::get('/background-jobs/{id}', [BackgroundJobDashboardController::class, 'show'])->name('background-jobs.show');
Route::post('/background-jobs/{id}/cancel', [BackgroundJobDashboardController::class, 'cancel'])->name('background-jobs.cancel');
Route::post('/background-jobs/{id}/retry', [BackgroundJobDashboardController::class, 'retry'])->name('background-jobs.retry');

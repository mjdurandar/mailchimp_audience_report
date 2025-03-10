<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login'); // Redirect to login page
});

// Route::get('/', function () {
//     return view('welcome'); // Redirect to login page
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/report', [ReportController::class, 'index'])->name('report');
    Route::get('/report/mailchimp-audience', [ReportController::class, 'showAudience'])->name('mailchimp.audience');
    Route::get('report/mailchimp/subscribers/{audienceId}', [ReportController::class, 'getSubscribers']);



    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\PromotionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'trainer'])->group(function () {
    Route::resource('promotions', PromotionController::class)->except(['show', 'destroy']);
    Route::patch('promotions/{promotion}/archive', [PromotionController::class, 'archive'])->name('promotions.archive');
    Route::patch('promotions/{promotion}/unarchive', [PromotionController::class, 'unarchive'])->name('promotions.unarchive');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LearnerInvitationController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionLearnerController;
use App\Http\Controllers\PromotionLearnerImportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'trainer'])->group(function () {
    Route::resource('promotions', PromotionController::class)->except(['destroy']);
    Route::patch('promotions/{promotion}/archive', [PromotionController::class, 'archive'])->name('promotions.archive');
    Route::patch('promotions/{promotion}/unarchive', [PromotionController::class, 'unarchive'])->name('promotions.unarchive');

    Route::post('promotions/{promotion}/learners', [PromotionLearnerController::class, 'store'])->name('promotions.learners.store');
    Route::post('promotions/{promotion}/learners/import', [PromotionLearnerImportController::class, 'store'])->name('promotions.learners.import');
    Route::delete('promotions/{promotion}/learners/{learner}', [PromotionLearnerController::class, 'destroy'])->name('promotions.learners.destroy');

    Route::post('learners/{learner}/invitation', [LearnerInvitationController::class, 'store'])->name('learners.invitation.resend');
});

Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('invitation/{user}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('invitation/{user}', [InvitationController::class, 'accept'])
        ->middleware('throttle:6,1')
        ->name('invitation.accept');
});

require __DIR__.'/settings.php';

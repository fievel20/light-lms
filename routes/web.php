<?php

use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrameworkController;
use App\Http\Controllers\FrameworkImportController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LearnerAssessmentController;
use App\Http\Controllers\LearnerInvitationController;
use App\Http\Controllers\MyCompetencyController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionLearnerController;
use App\Http\Controllers\PromotionLearnerImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'learner'])->group(function () {
    Route::get('mes-competences', [MyCompetencyController::class, 'index'])->name('my-competencies.index');
    Route::post('mes-competences/{promotion}/{competency}', [MyCompetencyController::class, 'store'])->name('my-competencies.store');
});

Route::middleware(['auth', 'verified', 'trainer'])->group(function () {
    Route::resource('promotions', PromotionController::class)->except(['destroy']);
    Route::get('promotions/{promotion}/suivi', [PromotionController::class, 'progress'])->name('promotions.progress');
    Route::patch('promotions/{promotion}/archive', [PromotionController::class, 'archive'])->name('promotions.archive');
    Route::patch('promotions/{promotion}/unarchive', [PromotionController::class, 'unarchive'])->name('promotions.unarchive');

    Route::post('promotions/{promotion}/learners', [PromotionLearnerController::class, 'store'])->name('promotions.learners.store');
    Route::post('promotions/{promotion}/learners/import', [PromotionLearnerImportController::class, 'store'])->name('promotions.learners.import');
    Route::delete('promotions/{promotion}/learners/{learner}', [PromotionLearnerController::class, 'destroy'])->name('promotions.learners.destroy');
    Route::get('promotions/{promotion}/learners/{learner}', [LearnerAssessmentController::class, 'show'])->name('promotions.learners.show');
    Route::post('promotions/{promotion}/learners/{learner}/assessments/{competency}', [LearnerAssessmentController::class, 'store'])->name('promotions.learners.assessments.store');

    Route::post('learners/{learner}/invitation', [LearnerInvitationController::class, 'store'])->name('learners.invitation.resend');

    Route::post('frameworks/import', [FrameworkImportController::class, 'store'])->name('frameworks.import');
    Route::resource('frameworks', FrameworkController::class);
    Route::resource('frameworks.competencies', CompetencyController::class)
        ->except(['index', 'show'])
        ->scoped();
});

Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('invitation/{user}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('invitation/{user}', [InvitationController::class, 'accept'])
        ->middleware('throttle:6,1')
        ->name('invitation.accept');
});

require __DIR__.'/settings.php';

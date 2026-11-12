<?php

use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\CurrentPromotionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrameworkController;
use App\Http\Controllers\FrameworkImportController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LearnerAccountController;
use App\Http\Controllers\LearnerAssessmentController;
use App\Http\Controllers\LearnerInvitationController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LearningPathSequenceController;
use App\Http\Controllers\MyCompetencyController;
use App\Http\Controllers\MyLearningPathController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionLearnerController;
use App\Http\Controllers\PromotionLearnerImportController;
use App\Http\Controllers\PromotionLearningPathController;
use App\Http\Controllers\SequenceController;
use App\Http\Controllers\SequenceImageController;
use App\Http\Controllers\SequenceReleaseController;
use App\Http\Controllers\TrainerCompetencyController;
use App\Http\Controllers\TrainerLearnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    // Trainer, or a learner the sequence is opened to (checked in the controller).
    Route::get('sequences/{sequence}/images/{image}', [SequenceImageController::class, 'show'])
        ->scopeBindings()
        ->name('sequences.images.show');
    // Not /icons/: Apache serves its own images there (Alias /icons/ in mods-enabled/alias.conf).
    Route::get('icones/{icon}', [IconController::class, 'show'])->name('icons.show');
});

Route::middleware(['auth', 'verified', 'learner'])->group(function () {
    Route::get('mes-competences', [MyCompetencyController::class, 'index'])->name('my-competencies.index');
    Route::post('mes-competences/{promotion}/{competency}', [MyCompetencyController::class, 'store'])->name('my-competencies.store');
    Route::get('mes-parcours', [MyLearningPathController::class, 'index'])->name('my-learning-paths.index');
    Route::get('mes-parcours/{promotion}/{learning_path}/{sequence}', [MyLearningPathController::class, 'show'])->name('my-learning-paths.show');
});

Route::middleware(['auth', 'verified', 'trainer'])->group(function () {
    Route::resource('promotions', PromotionController::class)->except(['destroy']);
    Route::get('promotions/{promotion}/suivi', [PromotionController::class, 'progress'])->name('promotions.progress');
    Route::put('promotion-active', [CurrentPromotionController::class, 'update'])->name('current-promotion.update');
    Route::get('promotions/{promotion}/competences', [TrainerCompetencyController::class, 'index'])->name('promotions.competencies.index');
    Route::get('promotions/{promotion}/competences/{competency}', [TrainerCompetencyController::class, 'show'])->name('promotions.competencies.show');
    Route::get('promotions/{promotion}/apprenants', [TrainerLearnerController::class, 'index'])->name('promotions.learners.index');
    Route::patch('promotions/{promotion}/archive', [PromotionController::class, 'archive'])->name('promotions.archive');
    Route::patch('promotions/{promotion}/unarchive', [PromotionController::class, 'unarchive'])->name('promotions.unarchive');

    Route::post('promotions/{promotion}/learners', [PromotionLearnerController::class, 'store'])->name('promotions.learners.store');
    Route::post('promotions/{promotion}/learners/import', [PromotionLearnerImportController::class, 'store'])->name('promotions.learners.import');
    Route::delete('promotions/{promotion}/learners/{learner}', [PromotionLearnerController::class, 'destroy'])->name('promotions.learners.destroy');
    Route::get('promotions/{promotion}/apprenants/{learner}', [LearnerAssessmentController::class, 'show'])->name('promotions.learners.show');
    Route::post('promotions/{promotion}/learners/{learner}/assessments/{competency}', [LearnerAssessmentController::class, 'store'])->name('promotions.learners.assessments.store');

    Route::post('learners/{learner}/invitation', [LearnerInvitationController::class, 'store'])->name('learners.invitation.resend');
    Route::get('comptes-apprenants', [LearnerAccountController::class, 'index'])->name('learners.index');
    Route::get('comptes-apprenants/{learner}', [LearnerAccountController::class, 'edit'])->name('learners.edit');
    Route::put('learners/{learner}/email', [LearnerAccountController::class, 'updateEmail'])->name('learners.email.update');
    Route::post('learners/{learner}/password-reset-link', [LearnerAccountController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('learners.password.reset-link');
    Route::put('learners/{learner}/password', [LearnerAccountController::class, 'updatePassword'])->name('learners.password.update');

    Route::post('frameworks/import', [FrameworkImportController::class, 'store'])->name('frameworks.import');
    Route::resource('frameworks', FrameworkController::class);
    Route::resource('frameworks.competencies', CompetencyController::class)
        ->except(['index', 'show'])
        ->scoped();

    Route::post('sequences/preview', [SequenceController::class, 'preview'])->name('sequences.preview');
    Route::post('sequences/{sequence}/duplicate', [SequenceController::class, 'duplicate'])->name('sequences.duplicate');
    Route::post('sequences/{sequence}/images', [SequenceImageController::class, 'store'])->name('sequences.images.store');
    Route::resource('sequences', SequenceController::class);

    Route::resource('learning-paths', LearningPathController::class);
    Route::resource('icones', IconController::class)
        ->parameters(['icones' => 'icon'])
        ->names('icons')
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('promotions/{promotion}/parcours', [PromotionLearningPathController::class, 'index'])->name('promotions.learning-paths.index');
    Route::post('promotions/{promotion}/parcours', [PromotionLearningPathController::class, 'store'])->name('promotions.learning-paths.store');
    Route::delete('promotions/{promotion}/parcours/{learning_path}', [PromotionLearningPathController::class, 'destroy'])->name('promotions.learning-paths.destroy');
    Route::put('promotions/{promotion}/sequences/{sequence}/release', [SequenceReleaseController::class, 'update'])->name('promotions.sequences.release');
    Route::post('learning-paths/{learning_path}/sequences', [LearningPathSequenceController::class, 'store'])->name('learning-paths.sequences.store');
    Route::put('learning-paths/{learning_path}/sequences', [LearningPathSequenceController::class, 'reorder'])->name('learning-paths.sequences.reorder');
    Route::delete('learning-paths/{learning_path}/sequences/{sequence}', [LearningPathSequenceController::class, 'destroy'])->name('learning-paths.sequences.destroy');
});

Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('invitation/{user}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('invitation/{user}', [InvitationController::class, 'accept'])
        ->middleware('throttle:6,1')
        ->name('invitation.accept');
});

require __DIR__.'/settings.php';

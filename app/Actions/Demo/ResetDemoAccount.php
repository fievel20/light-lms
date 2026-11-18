<?php

namespace App\Actions\Demo;

use App\Models\Assessment;
use App\Models\QuizAttempt;
use App\Models\SequenceCompletion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Empties the demo account: quiz attempts, finished sequences, self
 * assessments and celebrations are deleted, as if nobody had used it.
 */
class ResetDemoAccount
{
    public function handle(User $demo): void
    {
        if (! $demo->isDemo()) {
            return;
        }

        DB::transaction(function () use ($demo) {
            QuizAttempt::query()->where('user_id', $demo->id)->delete();
            SequenceCompletion::query()->where('user_id', $demo->id)->delete();
            Assessment::query()->where('learner_id', $demo->id)->delete();
            DB::table('competency_celebrations')->where('learner_id', $demo->id)->delete();
        });
    }
}

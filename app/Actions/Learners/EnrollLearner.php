<?php

namespace App\Actions\Learners;

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\LearnerInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollLearner
{
    /**
     * Enroll a learner in a promotion, creating and inviting the account if needed.
     *
     * Existing accounts are matched by email and left untouched.
     *
     * @throws EnrollmentException
     */
    public function handle(Promotion $promotion, string $firstName, string $lastName, string $email): EnrollmentResult
    {
        if ($promotion->isArchived()) {
            throw new EnrollmentException('Impossible d\'ajouter un apprenant à une promotion archivée.');
        }

        $email = Str::lower(trim($email));
        $learner = User::where('email', $email)->first();

        if ($learner?->isTrainer()) {
            throw new EnrollmentException('Cette adresse appartient au compte formateur.');
        }

        if ($learner && $promotion->learners()->whereKey($learner->id)->exists()) {
            throw new EnrollmentException('Cet apprenant fait déjà partie de la promotion.');
        }

        if ($learner) {
            $promotion->learners()->attach($learner);

            return EnrollmentResult::Enrolled;
        }

        $learner = DB::transaction(function () use ($promotion, $firstName, $lastName, $email): User {
            $learner = User::create([
                'first_name' => trim($firstName),
                'last_name' => trim($lastName),
                'email' => $email,
            ]);

            $promotion->learners()->attach($learner);

            return $learner;
        });

        $this->invite($learner);

        return EnrollmentResult::Created;
    }

    /**
     * Send (or send again) the invitation email.
     */
    public function invite(User $learner): void
    {
        $learner->forceFill(['invited_at' => now()])->save();

        $learner->notify(new LearnerInvitation);
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * The trainer sees the application as one of the learners, read only. The
 * session stays the trainer's: only the user of each request is swapped
 * (see ObserveLearner).
 */
class ObserverMode
{
    public const string LEARNER_KEY = 'observer.learner_id';

    public const string RETURN_KEY = 'observer.return_to';

    public function __construct(private Request $request) {}

    public function start(User $learner, string $returnTo): void
    {
        $this->request->session()->put([
            self::LEARNER_KEY => $learner->id,
            self::RETURN_KEY => $returnTo,
        ]);
    }

    /**
     * Ends observing; answers where the trainer was when it started.
     */
    public function stop(): string
    {
        $returnTo = $this->request->session()->pull(self::RETURN_KEY);
        $this->request->session()->forget(self::LEARNER_KEY);

        return is_string($returnTo) ? $returnTo : route('dashboard');
    }

    public function learnerId(): ?int
    {
        if (! $this->request->hasSession()) {
            return null;
        }

        $id = $this->request->session()->get(self::LEARNER_KEY);

        return is_int($id) ? $id : null;
    }

    public function active(): bool
    {
        return $this->learnerId() !== null;
    }
}

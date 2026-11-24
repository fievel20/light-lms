<?php

namespace App\Http\Controllers;

use App\Actions\Express\ExpressGame;
use App\Models\ExpressParticipant;
use App\Models\ExpressQuiz;
use App\Models\ExpressSession;
use App\Support\ExpressState;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The trainer leads a live session from the projected screen: QR code and
 * waiting room, then question after question, ranking and correction.
 */
class ExpressSessionController extends Controller
{
    public function __construct(private ExpressGame $game, private ExpressState $state) {}

    /**
     * The sessions of a quiz express, latest first.
     */
    public function index(ExpressQuiz $expressQuiz): Response
    {
        return Inertia::render('express/Sessions', [
            'quiz' => $expressQuiz->only(['id', 'title']),
            'sessions' => $expressQuiz->sessions()
                ->withCount('participants')
                ->latest('id')
                ->get()
                ->map(fn (ExpressSession $session) => [
                    'id' => $session->id,
                    'code' => $session->code,
                    'status' => $session->currentStatus()->value,
                    'questions' => $session->questionCount(),
                    'participants' => (int) $session->participants_count,
                    'created_at' => $session->created_at,
                ]),
        ]);
    }

    /**
     * A session afterwards: its ranking and its correction.
     */
    public function results(ExpressSession $session): Response
    {
        $state = $this->state->host($session);

        return Inertia::render('express/Results', [
            'quiz' => ['id' => $session->express_quiz_id, 'title' => $state['title']],
            'session' => [
                'id' => $session->id,
                'code' => $session->code,
                'status' => $state['status'],
                'created_at' => $session->created_at,
            ],
            'ranking' => $state['ranking'],
            'correction' => $state['correction'],
        ]);
    }

    public function destroy(ExpressSession $session): RedirectResponse
    {
        $session->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session supprimée.']);

        return to_route('express-quizzes.sessions.index', $session->express_quiz_id);
    }

    /**
     * Opens a session (or goes back to the one still open).
     */
    public function store(ExpressQuiz $expressQuiz): RedirectResponse
    {
        if (! $expressQuiz->questions()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Ajoute au moins une question avant de lancer le quiz.']);

            return back();
        }

        return to_route('express-sessions.show', $this->game->launch($expressQuiz));
    }

    public function show(ExpressSession $session): Response
    {
        $joinUrl = route('express.play.show', $session->code);

        return Inertia::render('express/Host', [
            'joinUrl' => $joinUrl,
            'shortUrl' => route('express.play.find'),
            'qrCode' => $this->qrCode($joinUrl),
            'quizId' => $session->express_quiz_id,
            'sessionId' => $session->id,
            'state' => $this->state->host($session),
        ]);
    }

    public function state(ExpressSession $session): JsonResponse
    {
        return response()->json($this->state->host($session));
    }

    public function next(ExpressSession $session): JsonResponse
    {
        $this->game->next($session);

        return $this->state($session);
    }

    public function correct(ExpressSession $session): JsonResponse
    {
        $this->game->correct($session);

        return $this->state($session);
    }

    public function close(ExpressSession $session): JsonResponse
    {
        $this->game->close($session);

        return $this->state($session);
    }

    /**
     * Removes someone from the session (an inappropriate name, say).
     */
    public function removeParticipant(ExpressSession $session, ExpressParticipant $participant): JsonResponse
    {
        abort_unless($participant->express_session_id === $session->id, 404);

        $participant->delete();

        return $this->state($session);
    }

    private function qrCode(string $url): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(320, 1), new SvgImageBackEnd)))->writeString($url);

        // Without the XML declaration, to be inlined in the page.
        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}

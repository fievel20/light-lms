<?php

namespace App\Http\Controllers;

use App\Actions\Express\ExpressGame;
use App\Models\ExpressImage;
use App\Models\ExpressParticipant;
use App\Models\ExpressSession;
use App\Support\ExpressState;
use App\Support\TextImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A participant's side of a quiz express, without an account: the QR code
 * leads here; a name is enough to join. The browser keeps a token (cookie)
 * to come back to the same place after a reload.
 */
class ExpressPlayController extends Controller
{
    /** Width of the drawn text, in pixels, and its size; shown smaller on a phone. */
    private const array TEXT_SIZES = ['q' => [800, 44], 'choice' => [640, 40]];

    /** Ink of the drawn text: dark on the white question, as the tiles need. */
    private const array TEXT_COLORS = ['q' => '#0e0926', 'a' => '#ffffff', 'b' => '#ffffff', 'c' => '#15093a', 'd' => '#0e0926'];

    public function __construct(private ExpressGame $game, private ExpressState $state) {}

    /**
     * Typing the code shown on the screen, for those without a QR reader.
     */
    public function find(Request $request): Response|RedirectResponse
    {
        if ($request->filled('code')) {
            $code = ExpressSession::normalizeCode((string) $request->string('code'));

            if (ExpressSession::query()->open()->where('code', $code)->exists()) {
                return to_route('express.play.show', $code);
            }

            return back()->withErrors(['code' => 'Aucun quiz en cours avec ce code.']);
        }

        return Inertia::render('express/Find');
    }

    public function show(Request $request, string $code): Response
    {
        $session = $this->session($code);

        return Inertia::render('express/Play', [
            'state' => $this->state->player($session, $this->participant($request, $session)),
            'maxName' => ExpressParticipant::MAX_NAME,
        ]);
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        $session = $this->session($code);

        if ($this->participant($request, $session) !== null) {
            return to_route('express.play.show', $session->code);
        }

        $validated = $request->validate(
            ['name' => ['required', 'string', 'max:'.ExpressParticipant::MAX_NAME]],
            attributes: ['name' => 'nom'],
        );

        [, $token] = $this->game->join($session, $validated['name']);

        Cookie::queue($this->cookieName($session), $token, ExpressSession::LIFETIME_HOURS * 60);

        return to_route('express.play.show', $session->code);
    }

    public function state(Request $request, string $code): JsonResponse
    {
        $session = $this->session($code);

        return response()->json($this->state->player($session, $this->participant($request, $session)));
    }

    public function answer(Request $request, string $code): JsonResponse
    {
        $session = $this->session($code);
        $participant = $this->participant($request, $session) ?? abort(403);

        $validated = $request->validate([
            'position' => ['required', 'integer'],
            'choice' => ['required', 'string', 'size:1'],
        ]);

        $this->game->answer($session, $participant, (int) $validated['position'], $validated['choice']);

        return response()->json($this->state->player($session, $participant));
    }

    /**
     * The text of a question ('q') or of one of its choices, drawn as an
     * image: only for participants, and not before the question is asked.
     */
    public function text(Request $request, string $code, int $position, string $part, TextImage $images): HttpResponse
    {
        $session = $this->session($code);
        $question = $this->visibleQuestion($request, $session, $position);

        $text = $part === 'q' ? $question['question'] : ($question['choices'][$part] ?? abort(404));
        [$width, $size] = self::TEXT_SIZES[$part === 'q' ? 'q' : 'choice'];

        $disk = Storage::disk(ExpressImage::DISK);
        $path = ExpressSession::directory($session->id)."/{$position}-{$part}.png";

        if (! $disk->exists($path)) {
            abort_unless($images->available(), 404);
            $disk->put($path, $images->png($text, $width, $size, self::TEXT_COLORS[$part] ?? '#0e0926'));
        }

        return response((string) $disk->get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=43200',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * The picture of a question, under the same conditions.
     */
    public function picture(Request $request, string $code, int $position): StreamedResponse
    {
        $session = $this->session($code);
        $question = $this->visibleQuestion($request, $session, $position);

        $image = ExpressImage::query()
            ->where('express_quiz_id', $session->express_quiz_id)
            ->find($question['image_id'])
            ?? abort(404);

        return ExpressImageController::stream($image);
    }

    private function session(string $code): ExpressSession
    {
        return ExpressSession::query()
            ->with('quiz:id,title')
            ->where('code', ExpressSession::normalizeCode($code))
            ->firstOr(fn () => abort(404, 'Aucun quiz avec ce code.'));
    }

    private function participant(Request $request, ExpressSession $session): ?ExpressParticipant
    {
        $token = $request->cookie($this->cookieName($session));

        return is_string($token) && $token !== ''
            ? $session->participants()->where('token_hash', ExpressParticipant::hashToken($token))->first()
            : null;
    }

    /**
     * A question a participant may see: asked already, or all of them once
     * the answers are revealed.
     *
     * @return array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string}
     */
    private function visibleQuestion(Request $request, ExpressSession $session, int $position): array
    {
        abort_if($this->participant($request, $session) === null, 403);

        $status = $session->currentStatus();
        $asked = $status->revealsAnswers() || $position <= ($session->current_position ?? 0);

        abort_unless($asked, 404);

        return $session->question($position) ?? abort(404);
    }

    private function cookieName(ExpressSession $session): string
    {
        return "express_{$session->id}";
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use App\Models\Sequence;
use App\Support\SvgIcon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IconController extends Controller
{
    /** Kilobytes; an icon is a few KB, a large drawing a few hundred. */
    public const int MAX_SIZE = 512;

    public function index(): Response
    {
        // Shown before deleting: the icon disappears from these sequences.
        $sequences = Sequence::query()
            ->where('content', 'like', '%(icon:%')
            ->orderBy('title')
            ->get(['title', 'content']);

        return Inertia::render('icons/Index', [
            'icons' => Icon::query()->orderBy('name')->get()->map(fn (Icon $icon) => [
                ...$icon->only(['id', 'name', 'monochrome']),
                'url' => route('icons.show', $icon),
                'markdown' => $icon->markdown(),
                'sequences' => $sequences
                    ->filter(fn (Sequence $sequence) => str_contains($sequence->content, "(icon:{$icon->id})"))
                    ->pluck('title')
                    ->values(),
            ]),
        ]);
    }

    /**
     * Imports one or more SVG files at once; nothing is saved if one is refused.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'icons' => ['required', 'array', 'max:50'],
            'icons.*' => ['required', 'file', 'extensions:svg', 'max:'.self::MAX_SIZE],
        ], [
            'icons.*.extensions' => '« :input » n’est pas un fichier SVG.',
            'icons.*.max' => 'Un fichier dépasse :max Ko.',
        ]);

        /** @var list<UploadedFile> $files */
        $files = $request->file('icons');
        $cleaned = [];

        foreach ($files as $index => $file) {
            $cleaned[$index] = SvgIcon::clean((string) $file->get())
                ?? throw ValidationException::withMessages([
                    "icons.{$index}" => "« {$file->getClientOriginalName()} » n’est pas un dessin SVG valide.",
                ]);
        }

        foreach ($files as $index => $file) {
            $path = Icon::DIRECTORY.'/'.Str::uuid().'.svg';
            Storage::disk(Icon::DISK)->put($path, $cleaned[$index]->svg);

            Icon::create([
                'name' => $this->uniqueName($file->getClientOriginalName()),
                'path' => $path,
                'monochrome' => $cleaned[$index]->monochrome,
                'size' => strlen($cleaned[$index]->svg),
            ]);
        }

        $count = count($files);
        Inertia::flash('toast', ['type' => 'success', 'message' => $count > 1 ? "{$count} icônes importées." : 'Icône importée.']);

        return to_route('icons.index');
    }

    public function show(Icon $icon): StreamedResponse
    {
        return Storage::disk(Icon::DISK)->response($icon->path, null, [
            'Content-Type' => 'image/svg+xml',
            // Opened on its own, the file still cannot run anything.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=604800, immutable',
        ]);
    }

    public function update(Request $request, Icon $icon): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('icons')->ignore($icon)],
            'monochrome' => ['required', 'boolean'],
        ], ['name.unique' => 'Une autre icône porte déjà ce nom.']);

        $icon->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Icône mise à jour.']);

        return to_route('icons.index');
    }

    public function destroy(Icon $icon): RedirectResponse
    {
        $icon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Icône « {$icon->name} » supprimée."]);

        return to_route('icons.index');
    }

    /** "loupe-bleue.svg" → "loupe bleue", then "loupe bleue 2" if taken. */
    private function uniqueName(string $fileName): string
    {
        $base = Str::limit(trim((string) preg_replace('/[\s_-]+/', ' ', pathinfo($fileName, PATHINFO_FILENAME))), 55, '') ?: 'Icône';
        $name = $base;

        for ($n = 2; Icon::query()->where('name', $name)->exists(); $n++) {
            $name = "{$base} {$n}";
        }

        return $name;
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Frameworks\ImportFramework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;

class FrameworkImportController extends Controller
{
    public function store(Request $request, ImportFramework $import): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'extensions:json', 'max:1024']],
            attributes: ['file' => 'fichier'],
        );

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $framework = $import->handle($file->getContent());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Référentiel « {$framework->name} » importé ({$framework->competencies()->count()} compétences).",
        ]);

        return to_route('frameworks.show', $framework);
    }
}

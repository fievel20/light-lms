<?php

namespace App\Http\Controllers;

use App\Actions\Learners\ImportException;
use App\Actions\Learners\ImportLearners;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PromotionLearnerImportController extends Controller
{
    public function store(Request $request, Promotion $promotion, ImportLearners $import): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']],
            attributes: ['file' => 'fichier'],
        );

        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['file' => 'Impossible d\'importer des apprenants dans une promotion archivée.']);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        try {
            $report = $import->handle($promotion, $file->getContent());
        } catch (ImportException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        Inertia::flash('import', $report);

        return to_route('promotions.show', $promotion);
    }
}

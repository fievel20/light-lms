<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Sequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Opens a sequence to a promotion's learners, or hides it again.
 */
class SequenceReleaseController extends Controller
{
    public function update(Request $request, Promotion $promotion, Sequence $sequence): RedirectResponse
    {
        abort_unless(in_array($sequence->id, $promotion->pathSequenceIds(), true), 404);

        $visible = $request->validate(['visible' => ['required', 'boolean']])['visible'];

        if ($promotion->isArchived()) {
            throw ValidationException::withMessages(['visible' => 'Promotion archivée : les séquences ne s\'ouvrent plus.']);
        }

        if (filter_var($visible, FILTER_VALIDATE_BOOL)) {
            // Opening again keeps the first opening date.
            $promotion->sequenceReleases()->firstOrCreate(
                ['sequence_id' => $sequence->id],
                ['released_at' => now(), 'released_by' => $request->user()?->id],
            );
        } else {
            $promotion->sequenceReleases()->where('sequence_id', $sequence->id)->delete();
        }

        return back();
    }
}

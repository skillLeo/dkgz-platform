<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AssessorServiceArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceAreaController extends Controller
{
    public function index(Request $request): Response
    {
        $assessor = $request->user()->assessor;

        return Inertia::render('Portal/Einsatzgebiet', [
            'areas' => $assessor->serviceAreas()->orderBy('postal_code_from')->get()
                ->map(fn (AssessorServiceArea $area) => [
                    'id' => $area->id,
                    'from' => $area->postal_code_from,
                    'to' => $area->postal_code_to,
                    'label' => $area->label,
                    'range' => $area->range(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'postal_code_from' => ['required', 'digits:5'],
            'postal_code_to' => ['required', 'digits:5', 'gte:postal_code_from'],
            'label' => ['nullable', 'string', 'max:120'],
        ], [], [
            'postal_code_from' => 'die Start-Postleitzahl',
            'postal_code_to' => 'die End-Postleitzahl',
            'label' => 'die Bezeichnung',
        ]);

        $request->user()->assessor->serviceAreas()->create($data);

        return back()->with('success', 'Das Einsatzgebiet wurde hinzugefügt.');
    }

    public function destroy(Request $request, AssessorServiceArea $area): RedirectResponse
    {
        abort_unless($area->assessor_id === $request->user()->assessor->id, 403);

        $area->delete();

        return back()->with('success', 'Das Einsatzgebiet wurde entfernt.');
    }
}

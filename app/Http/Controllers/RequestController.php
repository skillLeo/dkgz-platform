<?php

namespace App\Http\Controllers;

use App\Actions\CreateServiceRequestAction;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\FunnelEvent;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function create(Request $request): Response
    {
        FunnelEvent::record('begonnen');

        $selected = $this->selectionFrom($request);

        // Somebody arriving from a service page has already answered the first
        // question by being there — they open on the second step, so the
        // browser never reports reaching it. Counted here instead, or the
        // funnel shows every one of them dropping out at step one, which is
        // most of the traffic now that every service page links this way.
        if ($selected !== []) {
            FunnelEvent::record('schritt_2');
        }

        return Inertia::render('Public/Anfrage', [
            'content' => Content::page('anfrage'),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'slug', 'name_de', 'description_de']),
            'selected' => $selected,
        ]);
    }

    /**
     * The service the visitor arrived having already chosen.
     *
     * Every "Gutachter anfragen" button on a service page names its own
     * service, so somebody who came from the Wertgutachten page is not asked
     * which assessment they want — they already said, by being there. A slug
     * that resolves to nothing is left unanswered rather than guessed at, and
     * the first step asks as usual.
     *
     * @return array<string, mixed>
     */
    private function selectionFrom(Request $request): array
    {
        $service = ServiceType::active()
            ->where('slug', $request->string('leistung')->toString())
            ->first(['id']);

        return $service === null ? [] : ['service_type_id' => $service->id];
    }

    public function store(StoreServiceRequestRequest $request, CreateServiceRequestAction $create): RedirectResponse
    {
        FunnelEvent::record('abgesendet');

        $serviceRequest = $create->execute(
            $request->validated(),
            $request->file('images'),
            ['ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 255)],
        );

        return redirect()->route('request.confirmation', $serviceRequest->reference);
    }

    /**
     * The browser reporting that somebody reached a later step.
     *
     * Anonymous: nothing about who, only that it happened. Rate-limited at the
     * route so the counter cannot be inflated by anybody with a loop.
     */
    public function trackStep(Request $request): JsonResponse
    {
        $step = $request->string('step')->toString();

        if ($step === 'schritt_2') {
            FunnelEvent::record($step);
        }

        return response()->json(['ok' => true]);
    }

    public function confirmation(string $reference): Response
    {
        $serviceRequest = ServiceRequest::with('serviceType')
            ->where('reference', $reference)
            ->firstOrFail();

        return Inertia::render('Public/AnfrageBestaetigung', [
            'content' => Content::page('bestaetigung'),
            'request' => [
                'reference' => $serviceRequest->reference,
                'postal_code' => $serviceRequest->postal_code,
                'city' => $serviceRequest->city,
                'service_type' => $serviceRequest->serviceType?->name_de,
            ],
        ]);
    }
}

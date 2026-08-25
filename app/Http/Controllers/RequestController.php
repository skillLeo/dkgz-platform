<?php

namespace App\Http\Controllers;

use App\Actions\CreateServiceRequestAction;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\FunnelEvent;
use App\Models\PostalCode;
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

        return Inertia::render('Public/Anfrage', [
            'content' => Content::page('anfrage'),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'slug', 'name_de', 'description_de']),
            'selected' => $this->selectionFrom($request),
        ]);
    }

    /**
     * What the visitor has already answered before arriving here.
     *
     * The homepage hero asks which assessment and which postal code, so
     * somebody who answered there must not be asked again — they come across
     * with both in the address, and the contact step opens directly. Anything
     * that does not resolve is simply left unanswered rather than guessed at,
     * and the first step asks for it as usual.
     *
     * @return array<string, mixed>
     */
    private function selectionFrom(Request $request): array
    {
        $service = ServiceType::active()
            ->where('slug', $request->string('leistung')->toString())
            ->first(['id', 'slug']);

        $code = preg_replace('/\D/', '', $request->string('plz')->toString());
        $city = strlen((string) $code) === 5 ? PostalCode::cityFor($code) : null;

        return array_filter([
            'service_type_id' => $service?->id,
            'postal_code' => $city === null ? null : $code,
            'city' => $city,
        ], fn ($value) => $value !== null);
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

        if (in_array($step, ['schritt_2', 'schritt_3'], true)) {
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

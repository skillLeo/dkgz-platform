<?php

namespace App\Http\Controllers;

use App\Actions\CreateServiceRequestAction;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Content;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/Anfrage', [
            'content' => Content::page('anfrage'),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'slug', 'name_de', 'description_de']),
            'imageUploadsEnabled' => Settings::bool('features.image_uploads', true),
            'maxImages' => Settings::int('business.max_images_per_request', 5),
            'maxUploadMb' => Settings::int('business.max_upload_mb', 10),
        ]);
    }

    public function store(StoreServiceRequestRequest $request, CreateServiceRequestAction $create): RedirectResponse
    {
        $serviceRequest = $create->execute(
            $request->validated(),
            $request->file('images'),
            ['ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 255)],
        );

        return redirect()->route('request.confirmation', $serviceRequest->reference);
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

<?php

namespace App\Http\Controllers;

use App\Actions\MatchRequestAction;
use App\Actions\StoreRequestImagesAction;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Jobs\NotifyRequestSubmittedJob;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Support\Content;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function store(
        StoreServiceRequestRequest $request,
        MatchRequestAction $match,
        StoreRequestImagesAction $storeImages,
    ): RedirectResponse {
        $data = $request->validated();

        $serviceRequest = DB::transaction(function () use ($data, $request) {
            return ServiceRequest::create([
                'reference' => ServiceRequest::nextReference(),
                'service_type_id' => $data['service_type_id'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'vehicle_make' => $data['vehicle_make'],
                'vehicle_model' => $data['vehicle_model'],
                'vehicle_year' => $data['vehicle_year'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'vehicle_vin' => $data['vehicle_vin'] ?? null,
                'description' => $data['description'] ?? null,
                'preferred_date' => $data['preferred_date'] ?? null,
                'urgency' => $data['urgency'] ?? null,
                'status' => ServiceRequest::STATUS_NEW,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                // The GDPR record: when consent was given, not merely that it was.
                'consent_at' => now(),
            ]);
        });

        if (Settings::bool('features.image_uploads', true) && $request->hasFile('images')) {
            $storeImages->execute($serviceRequest, $request->file('images'));
        }

        NotifyRequestSubmittedJob::dispatch($serviceRequest->id);

        $match->execute($serviceRequest);

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

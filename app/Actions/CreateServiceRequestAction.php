<?php

namespace App\Actions;

use App\Jobs\NotifyOfficeOfRequestJob;
use App\Jobs\NotifyRequestSubmittedJob;
use App\Models\PostalCode;
use App\Models\ServiceRequest;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates a request and starts everything that follows from one.
 *
 * The office needs to take requests over the telephone, and a customer who
 * rings rather than types should end up in exactly the same place: the same
 * confirmation e-mail, the same matching run, the same partners notified. That
 * only stays true if there is one path — so the public form and the admin
 * screen both call this, and neither can drift from the other by accident.
 */
class CreateServiceRequestAction
{
    /**
     * @param  array<string, mixed>  $data  Validated request data.
     * @param  array<int, UploadedFile>|null  $images
     * @param  array<string, string|null>  $origin  ip and user agent, where a
     *                                              browser supplied them. A request taken by telephone has neither, and
     *                                              recording the office's own address as the customer's would be a lie in
     *                                              the one field that exists to answer "where did this come from".
     */
    public function execute(array $data, ?array $images = null, array $origin = []): ServiceRequest
    {
        $serviceRequest = DB::transaction(fn () => ServiceRequest::create([
            'reference' => ServiceRequest::nextReference(),
            'service_type_id' => $data['service_type_id'],
            'postal_code' => $data['postal_code'],
            'city' => $data['city'] ?? PostalCode::cityFor($data['postal_code']) ?? '',
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'],
            'vehicle_make' => $data['vehicle_make'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'vehicle_year' => $data['vehicle_year'] ?? null,
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
            'vehicle_vin' => $data['vehicle_vin'] ?? null,
            'description' => $data['description'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'urgency' => $data['urgency'] ?? null,
            'status' => ServiceRequest::STATUS_NEW,
            'ip_address' => $origin['ip'] ?? null,
            'user_agent' => $origin['user_agent'] ?? null,
            // The GDPR record: when consent was given, not merely that it was.
            // Taken by telephone, the operator confirms it was given on the
            // call; the timestamp means the same thing either way.
            'consent_at' => now(),
        ]));

        if (Settings::bool('features.image_uploads', true) && ! empty($images)) {
            app(StoreRequestImagesAction::class)->execute($serviceRequest, $images);
        }

        NotifyRequestSubmittedJob::dispatch($serviceRequest->id);

        // And the office, so somebody knows a customer is waiting without
        // having to open the admin panel to find out.
        NotifyOfficeOfRequestJob::dispatch($serviceRequest->id);

        app(MatchRequestAction::class)->execute($serviceRequest);

        return $serviceRequest;
    }
}

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
    /**
     * A request the office made to watch the flow work, rather than a customer.
     *
     * Recognised by the postal code alone, because that is the one field on the
     * short form that can carry a signal nobody would type by accident. Empty
     * setting means no such code exists and nothing is ever treated as a test.
     */
    public static function isTest(?string $postalCode): bool
    {
        $secret = trim(Settings::get('features.test_postal_code', ''));

        return $secret !== '' && trim((string) $postalCode) === $secret;
    }

    public function execute(array $data, ?array $images = null, array $origin = []): ServiceRequest
    {
        $isTest = self::isTest($data['postal_code'] ?? null);

        $serviceRequest = DB::transaction(fn () => ServiceRequest::create([
            'reference' => ServiceRequest::nextReference(),
            'service_type_id' => $data['service_type_id'],
            'requested_assessor_id' => $data['requested_assessor_id'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? (isset($data['postal_code']) ? PostalCode::cityFor($data['postal_code']) : null),
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
            'is_test' => $isTest,
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

        // Everything above happens for a test too — the confirmation to whoever
        // submitted it, the note to the office — because that is the flow being
        // tested. Matching is where it stops: this is the step that puts a job
        // in front of real partners, and they must never be shown one that does
        // not exist.
        if (! $isTest) {
            app(MatchRequestAction::class)->execute($serviceRequest);
        }

        return $serviceRequest;
    }
}

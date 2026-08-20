<?php

namespace App\Http\Resources;

use App\Models\Assignment;
use App\Models\ServiceRequest;
use App\Support\Formatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The privacy boundary.
 *
 * Customer name, telephone and e-mail are stripped here, at the resource layer,
 * for anyone who has not accepted this request. They never reach the Inertia
 * payload, so there is nothing in the browser to un-hide — masking in Vue would
 * only have hidden them from the eye, not from the network tab.
 *
 * @mixin ServiceRequest
 */
class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'service_type' => $this->whenLoaded('serviceType', fn () => [
                'id' => $this->serviceType->id,
                'name' => $this->serviceType->name_de,
            ]),
            // Shown to the partner before they decide — the client's explicit
            // requirement is that the exact fee is visible immediately.
            'dkgz_fee_cents' => $this->whenLoaded('serviceType', fn () => $this->serviceType->dkgz_fee_cents),
            'dkgz_fee_label' => $this->whenLoaded(
                'serviceType',
                fn () => $this->serviceType->dkgz_fee_cents === null
                    ? null
                    : Formatter::money($this->serviceType->dkgz_fee_cents)
            ),
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'location' => $this->locationLabel(),
            'vehicle' => $this->vehicleLabel(),
            'vehicle_make' => $this->vehicle_make,
            'vehicle_model' => $this->vehicle_model,
            'vehicle_year' => $this->vehicle_year,
            'vehicle_plate' => $this->vehicle_plate,
            'description' => $this->description,
            'urgency' => $this->urgency,
            'urgency_label' => $this->urgencyLabel(),
            'preferred_date' => $this->preferred_date,
            'created_at' => $this->created_at,
            'created_at_label' => Formatter::dateTime($this->created_at),
            'matched_count' => $this->matched_count,
            'image_count' => $this->whenCounted('images'),
        ];

        if ($this->viewerHasAccepted($request)) {
            $payload['customer'] = [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'phone_label' => Formatter::phone($this->customer_phone),
                'email' => $this->customer_email,
            ];
            $payload['contact_released'] = true;
        } else {
            $payload['customer'] = null;
            $payload['contact_released'] = false;
        }

        return $payload;
    }

    /**
     * Staff with assignments.view see the customer; an assessor sees them only
     * once they hold the assignment for this request.
     */
    private function viewerHasAccepted(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user->can('requests.view')) {
            return true;
        }

        $assessorId = $user->assessor?->id;

        if ($assessorId === null) {
            return false;
        }

        return Assignment::where('service_request_id', $this->id)
            ->where('assessor_id', $assessorId)
            ->exists();
    }

    private function urgencyLabel(): ?string
    {
        return match ($this->urgency) {
            'normal' => 'Keine besondere Eile',
            'soon' => 'Innerhalb von zwei Werktagen',
            'urgent' => 'So schnell wie möglich',
            default => null,
        };
    }
}

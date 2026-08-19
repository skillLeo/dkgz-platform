<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sent on the configured delay after completion. The design specifies this as
 * its own template ("05 Bewertungsanfrage"), separate from the completion mail.
 */
class SendReviewRequestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [300, 1800, 3600];

    public function __construct(private readonly int $assignmentId) {}

    public function handle(): void
    {
        $assignment = Assignment::with([
            'serviceRequest.serviceType',
            'assessor',
            'review',
        ])->find($this->assignmentId);

        $review = $assignment?->review;
        $request = $assignment?->serviceRequest;

        // Nothing to ask for if they already answered, or the link has lapsed.
        if ($assignment === null || $request === null || $review === null || ! $review->isUsable()) {
            return;
        }

        Mailer::send($request->customer_email, 'bewertungsanfrage', [
            'eyebrow' => 'Ihre Rückmeldung',
            'headline' => 'Wie zufrieden waren Sie mit dem vermittelten Sachverständigen?',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'nachname' => $request->customer_name,
            'referenz' => $request->reference,
            'gutachtenart' => $request->serviceType?->name_de,
            'sv_firma' => $assignment->assessor?->company_name,
            'abschluss_datum' => Formatter::date($assignment->completed_at),
            'scale' => true,
            'dataTitle' => 'Ihr Vorgang',
            'rows' => [
                ['k' => 'Referenz', 'v' => $request->reference, 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Sachverständiger', 'v' => $assignment->assessor?->company_name],
                ['k' => 'Abgeschlossen am', 'v' => Formatter::date($assignment->completed_at), 'mono' => true],
            ],
            'cta' => 'Bewertung abgeben',
            'cta_url' => route('review.show', $review->token),
            'note' => 'Bei einer Bewertung von 7 oder weniger fragen wir in einem zweiten Schritt nach dem Grund. Diese Angaben sieht ausschließlich die DKGZ-Qualitätssicherung.',
            'footnote' => 'Dieser Bewertungslink ist 30 Tage gültig und nur einmal verwendbar.',
        ], related: $assignment);
    }
}

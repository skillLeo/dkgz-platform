<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Support\Formatter;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The completion mail carries the documents. The review request follows
 * separately on the configured delay — a different moment, its own token.
 */
class NotifyAssignmentCompletedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $assignmentId) {}

    public function handle(): void
    {
        $assignment = Assignment::with([
            'serviceRequest.serviceType',
            'assessor',
            'documents',
            'review',
        ])->find($this->assignmentId);

        if ($assignment === null || $assignment->serviceRequest === null) {
            return;
        }

        $request = $assignment->serviceRequest;

        // Available immediately: the token is created at completion, so the
        // customer can rate straight from this mail rather than waiting for the
        // separate reminder.
        $reviewUrl = $assignment->review !== null && Settings::bool('features.review_flow', true)
            ? route('review.show', $assignment->review->token)
            : null;

        // Neither the report nor any invoice travels with this message. The
        // assessor delivers their own work to their own customer and bills them
        // directly; DKGZ vermittelt and says so. Sending the documents on made
        // it look as though the gutachten came from us.
        Mailer::send($request->customer_email, 'auftrag-abgeschlossen', [
            'eyebrow' => 'Vorgang abgeschlossen',
            'headline' => 'Ihr Gutachten ist fertiggestellt.',
            'kunde' => $request->customer_name,
            'referenz' => $request->reference,
            'refLabel' => 'Ihre Vorgangsnummer',
            'sv_firma' => $assignment->assessor?->company_name,
            'gutachtenart' => $request->serviceType?->name_de,
            'abschluss_zeit' => Formatter::dateTime($assignment->completed_at),
            'dataTitle' => 'Abschluss',
            'rows' => array_values(array_filter([
                ['k' => 'Sachverständiger', 'v' => $assignment->assessor?->company_name],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Abgeschlossen am', 'v' => Formatter::dateTime($assignment->completed_at), 'mono' => true],
            ])),
            // The rating is the one thing we actually want the customer to do
            // next, so it is the button — not a sentence mentioning a link that
            // arrives separately days later.
            'cta' => $reviewUrl !== null ? 'Jetzt bewerten' : null,
            'cta_url' => $reviewUrl,
            'bewertung_url' => $reviewUrl,
        ], related: $assignment);

        // Opt-in since the Google review ask took over at acceptance. Both
        // would mean asking the same customer twice for the same thing a week
        // apart, which is how a request for goodwill spends it instead.
        if (Settings::bool('features.review_flow', true)
            && Settings::bool('features.internal_review_request', false)
            && $assignment->review !== null
        ) {
            SendReviewRequestJob::dispatch($assignment->id)
                ->delay(now()->addDays(Settings::int('business.review_delay_days', 3)));
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Models\AssignmentDocument;
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

        $attachments = $assignment->documents
            ->whereIn('type', [AssignmentDocument::TYPE_REPORT, AssignmentDocument::TYPE_CUSTOMER_INVOICE])
            ->pluck('path')
            ->all();

        Mailer::send($request->customer_email, 'auftrag-abgeschlossen', [
            'eyebrow' => 'Vorgang abgeschlossen',
            'headline' => 'Ihr Gutachten liegt vor.',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'nachname' => $request->customer_name,
            'referenz' => $request->reference,
            'refLabel' => 'Ihre Vorgangsnummer',
            'sv_firma' => $assignment->assessor?->company_name,
            'gutachtenart' => $request->serviceType?->name_de,
            'abschluss_zeit' => Formatter::dateTime($assignment->completed_at),
            'honorar' => Formatter::money($assignment->fee_cents),
            'dataTitle' => 'Abschluss',
            'rows' => array_values(array_filter([
                ['k' => 'Sachverständiger', 'v' => $assignment->assessor?->company_name],
                ['k' => 'Art des Gutachtens', 'v' => $request->serviceType?->name_de],
                ['k' => 'Abgeschlossen am', 'v' => Formatter::dateTime($assignment->completed_at), 'mono' => true],
                ...$assignment->documents->map(fn (AssignmentDocument $d) => [
                    'k' => $d->typeLabel(),
                    'v' => $d->original_name.' · '.Formatter::fileSize($d->size_bytes),
                ])->all(),
                ['k' => 'Honorar netto', 'v' => Formatter::money($assignment->fee_cents), 'mono' => true],
            ])),
            'cta' => 'Vorgang ansehen',
            'cta_url' => route('request.confirmation', $request->reference),
            'note' => 'Die Rechnung stellt der Sachverständige. DKGZ ist nicht Vertragspartner der Begutachtung und stellt Ihnen keine Kosten in Rechnung.',
        ], attachments: $attachments, related: $assignment);

        if (Settings::bool('features.review_flow', true) && $assignment->review !== null) {
            SendReviewRequestJob::dispatch($assignment->id)
                ->delay(now()->addDays(Settings::int('business.review_delay_days', 3)));
        }
    }
}

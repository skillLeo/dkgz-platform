<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Support\Mailer;
use App\Support\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Asks the customer for a Google review, a day after a partner took the job.
 *
 * The timing is the point. A day after acceptance the assessor has been in
 * touch and the customer knows their problem is being dealt with, which is when
 * they feel best about it — later, once the report is written and paid for, the
 * feeling has moved on and the moment has passed.
 *
 * It sends nothing if no review link is configured: an e-mail asking somebody
 * to leave a review with nowhere to leave it is worse than no e-mail.
 */
class RequestGoogleReviewJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [300, 1800, 3600];

    public function __construct(private readonly int $assignmentId) {}

    public function handle(): void
    {
        $url = trim((string) Settings::get('business.google_review_url'));

        if ($url === '') {
            return;
        }

        $assignment = Assignment::with(['serviceRequest.serviceType', 'assessor'])
            ->find($this->assignmentId);

        $request = $assignment?->serviceRequest;

        if ($request === null || blank($request->customer_email)) {
            return;
        }

        // A job that was cancelled between acceptance and this running should
        // not be followed by "thank you for choosing us".
        if ($assignment->status === Assignment::STATUS_CANCELLED) {
            return;
        }

        Mailer::send($request->customer_email, 'google-bewertung', [
            'eyebrow' => 'Vielen Dank',
            'headline' => 'Wie war Ihre Erfahrung mit DKGZ?',
            'salutation' => 'Guten Tag '.$request->customer_name.',',
            'kunde' => $request->customer_name,
            'nachname' => $request->customer_name,
            'referenz' => $request->reference,
            'gutachtenart' => $request->serviceType?->name_de,
            'sv_firma' => $assignment->assessor?->company_name,
            'cta' => 'Jetzt auf Google bewerten',
            'cta_url' => $url,
        ]);
    }
}

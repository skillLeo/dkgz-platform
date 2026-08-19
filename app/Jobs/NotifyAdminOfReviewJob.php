<?php

namespace App\Jobs;

use App\Models\CustomerReview;
use App\Support\Formatter;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAdminOfReviewJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $reviewId) {}

    public function handle(): void
    {
        $review = CustomerReview::with('assignment.serviceRequest', 'assignment.assessor')->find($this->reviewId);

        if ($review === null) {
            return;
        }

        Mailer::send(Mailer::adminRecipient(), 'bewertung-erhalten', [
            'eyebrow' => 'Bewertung',
            'headline' => "Neue Bewertung: {$review->rating} von 10",
            'salutation' => 'Guten Tag,',
            'referenz' => $review->assignment?->serviceRequest?->reference,
            'bewertung' => (string) $review->rating,
            'sv_firma' => $review->assignment?->assessor?->company_name,
            'kategorie' => $review->feedback_category ?? '',
            'anmerkung' => $review->feedback ?? '',
            'dataTitle' => 'Bewertung',
            'rows' => array_values(array_filter([
                ['k' => 'Referenz', 'v' => $review->assignment?->serviceRequest?->reference, 'mono' => true],
                ['k' => 'Bewertung', 'v' => "{$review->rating} von 10", 'mono' => true],
                ['k' => 'Sachverständiger', 'v' => $review->assignment?->assessor?->company_name],
                $review->feedback_category ? ['k' => 'Kategorie', 'v' => $review->feedback_category] : null,
                ['k' => 'Abgegeben am', 'v' => Formatter::dateTime($review->submitted_at), 'mono' => true],
            ])),
            'note' => $review->feedback,
        ], related: $review);
    }
}

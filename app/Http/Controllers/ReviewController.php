<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyAdminOfReviewJob;
use App\Models\CustomerReview;
use App\Support\Content;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The rating flow. A score at or above the configured threshold sends the
 * customer on to the public review page; anything lower routes into the
 * internal feedback step, which only DKGZ quality assurance ever reads.
 */
class ReviewController extends Controller
{
    public function show(string $token): Response
    {
        $review = $this->findUsable($token);

        $review->load('assignment.serviceRequest.serviceType', 'assignment.assessor');

        return Inertia::render('Public/Bewertung', [
            'content' => Content::page('bewertung'),
            'token' => $token,
            'review' => [
                'rating' => $review->rating,
                'submitted' => $review->isSubmitted(),
            ],
            'context' => [
                'reference' => $review->assignment?->serviceRequest?->reference,
                'service_type' => $review->assignment?->serviceRequest?->serviceType?->name_de,
                'city' => $review->assignment?->serviceRequest?->city,
                'company' => $review->assignment?->assessor?->company_name,
                'completed_at' => $review->assignment?->completed_at,
            ],
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $review = $this->findUsable($token);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'rating' => 'die Bewertung',
            'feedback' => 'die Anmerkung',
        ]);

        $review->update([
            'rating' => $data['rating'],
            'feedback' => $data['feedback'] ?? null,
            'submitted_at' => now(),
        ]);

        NotifyAdminOfReviewJob::dispatch($review->id);

        $threshold = Settings::int('business.review_min_rating', 8);

        // Below the threshold we ask what went wrong before thanking them.
        if ($data['rating'] < $threshold) {
            return redirect()->route('review.show', $token)->with('feedback_step', true);
        }

        return redirect()->route('review.thanks', $token);
    }

    public function feedback(Request $request, string $token): RedirectResponse
    {
        $review = CustomerReview::where('token', $token)->firstOrFail();

        abort_if($review->isExpired(), 410, 'Dieser Bewertungslink ist abgelaufen.');

        $data = $request->validate([
            'feedback_category' => ['required', Rule::in([
                'Erreichbarkeit des Sachverständigen',
                'Terminfindung',
                'Dauer bis zum Gutachten',
                'Qualität der Unterlagen',
                'Kommunikation',
                'Kosten',
                'Sonstiges',
            ])],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'may_contact' => ['boolean'],
        ], [], [
            'feedback_category' => 'die Kategorie',
            'feedback' => 'die Beschreibung',
        ]);

        $review->update([
            'feedback_category' => $data['feedback_category'],
            'feedback' => $data['feedback'] ?? $review->feedback,
        ]);

        return redirect()->route('review.thanks', $token);
    }

    public function thanks(string $token): Response|RedirectResponse
    {
        $review = CustomerReview::where('token', $token)->firstOrFail();

        $threshold = Settings::int('business.review_min_rating', 8);
        $redirectUrl = Settings::get('business.review_redirect_url');

        // A happy customer is sent on to the public profile, once.
        if ($review->qualifiesForPublicRedirect($threshold)
            && filled($redirectUrl)
            && $review->redirected_at === null) {
            $review->update(['redirected_at' => now()]);

            return redirect()->away($redirectUrl);
        }

        return Inertia::render('Public/BewertungDanke', [
            'content' => Content::page('bewertung'),
        ]);
    }

    private function findUsable(string $token): CustomerReview
    {
        $review = CustomerReview::where('token', $token)->firstOrFail();

        abort_if($review->isExpired(), 410, 'Dieser Bewertungslink ist abgelaufen.');

        return $review;
    }
}

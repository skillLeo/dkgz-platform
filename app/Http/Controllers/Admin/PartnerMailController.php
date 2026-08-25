<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPartnerBroadcastJob;
use App\Models\Assessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One message to every registered partner.
 *
 * A blunt instrument by nature, so the screen is built to slow the hand down:
 * it says how many people will receive it before the button is pressed, and it
 * offers a test send to one address first. Nobody unsends a mail to a hundred
 * assessors.
 */
class PartnerMailController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessor::class);

        return Inertia::render('Admin/Partnermail', [
            'audiences' => [
                [
                    'value' => 'approved',
                    'label' => 'Alle freigegebenen Partner',
                    'count' => $this->recipients('approved')->count(),
                ],
                [
                    'value' => 'available',
                    'label' => 'Nur derzeit verfügbare Partner',
                    'count' => $this->recipients('available')->count(),
                ],
                [
                    'value' => 'all',
                    'label' => 'Alle Partner, auch gesperrte und wartende',
                    'count' => $this->recipients('all')->count(),
                ],
            ],
            'canSend' => $request->user()->can('assessors.view'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Assessor::class);

        $data = $request->validate([
            'audience' => ['required', 'in:approved,available,all'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            // A dry run to one address, so the wording can be seen in a real
            // mail client before it goes to everybody.
            'test_email' => ['nullable', 'email'],
        ], [], [
            'subject' => 'der Betreff',
            'body' => 'der Text',
            'test_email' => 'die Testadresse',
        ]);

        if (filled($data['test_email'] ?? null)) {
            SendPartnerBroadcastJob::dispatch(
                $data['subject'],
                $data['body'],
                [$data['test_email']],
                $request->user()->id,
            );

            return back()->with('success', "Testnachricht an {$data['test_email']} versendet.");
        }

        $recipients = $this->recipients($data['audience'])
            ->with('user:id,email')
            ->get()
            ->pluck('user.email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($recipients)) {
            return back()->withErrors(['audience' => 'Für diese Auswahl gibt es keine Empfänger.']);
        }

        SendPartnerBroadcastJob::dispatch(
            $data['subject'],
            $data['body'],
            $recipients,
            $request->user()->id,
        );

        activity()->withProperties(['empfaenger' => count($recipients)])
            ->log('Rundmail an Partner: '.$data['subject']);

        return back()->with(
            'success',
            count($recipients).' Partner erhalten die Nachricht. Der Versand läuft im Hintergrund.'
        );
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Assessor> */
    private function recipients(string $audience)
    {
        return match ($audience) {
            'available' => Assessor::approved()->where('is_available', true),
            'all' => Assessor::query(),
            default => Assessor::approved(),
        };
    }
}

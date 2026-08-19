<?php

namespace App\Jobs;

use App\Models\Assessor;
use App\Support\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAssessorApprovalJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int $assessorId,
        private readonly string $outcome,
    ) {}

    public function handle(): void
    {
        $assessor = Assessor::with('user')->find($this->assessorId);
        $user = $assessor?->user;

        if ($user === null) {
            return;
        }

        [$key, $eyebrow, $headline, $note] = match ($this->outcome) {
            'freigegeben' => [
                'registrierung-freigegeben',
                'Freigabe',
                'Ihr Zugang ist freigegeben.',
                'Bitte prüfen Sie im Portal, ob Einsatzgebiet und Leistungen vollständig hinterlegt sind.',
            ],
            'abgelehnt' => [
                'registrierung-abgelehnt',
                'Registrierung',
                'Ihre Registrierung wurde nicht freigegeben.',
                $assessor->rejection_reason,
            ],
            'gesperrt' => [
                'konto-gesperrt',
                'Zugang',
                'Ihr Zugang wurde gesperrt.',
                $assessor->suspension_reason,
            ],
            default => [null, null, null, null],
        };

        if ($key === null) {
            return;
        }

        Mailer::send($user->email, $key, [
            'eyebrow' => $eyebrow,
            'headline' => $headline,
            'salutation' => 'Guten Tag '.$user->last_name.',',
            'sv_nachname' => $user->last_name,
            'firma' => $assessor->company_name,
            'grund' => $assessor->rejection_reason ?? $assessor->suspension_reason ?? '',
            'cta' => $this->outcome === 'freigegeben' ? 'Zum Partnerportal' : null,
            'cta_url' => $this->outcome === 'freigegeben' ? route('portal.dashboard') : null,
            'note' => $note,
        ], related: $assessor);
    }
}

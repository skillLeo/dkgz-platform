<?php

namespace App\Http\Controllers;

use App\Models\Assessor;
use App\Models\ServiceType;
use App\Support\Content;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public directory of partners.
 *
 * Two things at once. A hundred and thirty pages naming a firm, a region and
 * the assessments they carry out is a real SEO asset that nothing else on the
 * site provides. And somebody who has been recommended a particular assessor
 * can now reach them through DKGZ rather than around it — which only works if
 * the page gives them no way to go around it.
 *
 * So no telephone number, no e-mail address, no street: a listed partner is
 * asked for work through the platform and reached no other way.
 */
class DirectoryController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request): Response
    {
        $region = $request->string('region')->toString();
        $serviceSlug = $request->string('leistung')->toString();

        $service = ServiceType::active()->where('slug', $serviceSlug)->first(['id', 'slug', 'name_de']);

        $assessors = Assessor::query()
            ->listed()
            ->with(['activeServiceTypes:id,name_de'])
            // A leading postal digit is how German regions are laid out, and
            // the only geography a visitor can pick from without a map.
            ->when(strlen($region) === 1, fn ($q) => $q->where('postal_code', 'like', $region.'%'))
            ->when($service, fn ($q) => $q->whereHas(
                'activeServiceTypes',
                fn ($sub) => $sub->where('service_types.id', $service->id)
            ))
            ->orderBy('company_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Public/Sachverstaendige', [
            'content' => Content::page('verzeichnis'),
            'assessors' => $assessors->through(fn (Assessor $a) => $this->card($a)),
            'serviceTypes' => ServiceType::active()->ordered()->get(['slug', 'name_de']),
            'filters' => ['region' => $region ?: null, 'leistung' => $service?->slug],
            'total' => Assessor::listed()->count(),
        ]);
    }

    public function show(Assessor $assessor): Response
    {
        abort_unless($assessor->approval_status === Assessor::STATUS_APPROVED
            && $assessor->is_listed
            && $assessor->user?->is_active, 404);

        $assessor->load(['activeServiceTypes:id,name_de,slug,description_de', 'serviceAreas']);

        return Inertia::render('Public/Sachverstaendiger', [
            'content' => Content::page('verzeichnis'),
            // Merged rather than added: `+` keeps the left-hand value for a
            // key that appears on both sides, which silently left the profile
            // with the listing's bare service names instead of the linked ones.
            'assessor' => array_merge($this->card($assessor), [
                // Needed to aim the form on this page at this partner. Safe to
                // expose: the request validates that whatever id arrives
                // belongs to somebody approved and publicly listed.
                'id' => $assessor->id,
                'public_profile' => $assessor->public_profile,
                'certification_body' => $assessor->certification_body,
                'years_experience' => $assessor->years_experience,
                'services' => $assessor->activeServiceTypes->map(fn (ServiceType $t) => [
                    'name' => $t->name_de,
                    'description' => $t->description_de,
                    'url' => "/leistungen/{$t->slug}",
                ])->values(),
            ]),
            // Everything the shortened form on this page needs, and nothing
            // that would let it be pointed at somebody else.
            'requestServiceTypes' => $assessor->activeServiceTypes
                ->map(fn (ServiceType $t) => ['id' => $t->id, 'name_de' => $t->name_de]),
        ]);
    }

    /**
     * What may be said about a partner in public.
     *
     * The trading name, where they work, what they do. Deliberately not the
     * telephone number, the e-mail address or the street — those reach them
     * around the platform, and a partner is listed on the understanding that
     * work comes through it.
     *
     * @return array<string, mixed>
     */
    private function card(Assessor $assessor): array
    {
        return [
            'slug' => $assessor->slug,
            'name' => $assessor->company_name,
            'initials' => $assessor->initials(),
            'photo_url' => $assessor->photoUrl(),
            'region' => $assessor->regionLabel(),
            'city' => $assessor->city,
            'services' => $assessor->activeServiceTypes->pluck('name_de'),
            'url' => "/sachverstaendige/{$assessor->slug}",
        ];
    }
}

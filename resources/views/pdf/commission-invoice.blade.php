@php
    use App\Support\Formatter;
    use App\Support\Settings;

    $navy700 = '#14294A';
    $gray800 = '#2E3A4C';
    $gray600 = '#5B687C';
    $gray400 = '#9AA5B4';
    $gray200 = '#E3E7EE';
    $gray100 = '#F1F3F7';
    $accent = '#B08A2E';

    $assessor = $commission->assessor;
    $request = $commission->assignment?->serviceRequest;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    {{-- dompdf has no web font access and none is wanted: the built-in serif/sans
         keeps the file small and the output deterministic. --}}
    <style>
        @page { margin: 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.6; color: {{ $gray800 }}; }
        h1 { font-size: 18pt; font-weight: bold; color: {{ $navy700 }}; margin: 0; letter-spacing: -0.4pt; }
        .eyebrow { font-size: 7.5pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; color: {{ $gray600 }}; }
        .rule { width: 24pt; height: 1.5pt; background: {{ $accent }}; margin-top: 5pt; }
        .muted { color: {{ $gray600 }}; }
        .small { font-size: 8.5pt; }
        table { width: 100%; border-collapse: collapse; }
        .data td { padding: 6pt 0; border-bottom: 0.5pt solid {{ $gray100 }}; vertical-align: top; }
        .data td.label { color: {{ $gray600 }}; width: 42%; }
        .num { text-align: right; white-space: nowrap; }
        .total td { border-top: 1.5pt solid {{ $navy700 }}; padding: 8pt 0; font-weight: bold; color: {{ $navy700 }}; font-size: 12pt; }
        .foot { border-top: 0.5pt solid {{ $gray200 }}; padding-top: 8pt; font-size: 8pt; color: {{ $gray600 }}; }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="vertical-align: top;">
            <div style="font-size: 15pt; font-weight: bold; color: {{ $navy700 }}; letter-spacing: -0.5pt;">
                {{ Settings::get('branding.platform_name', 'DKGZ') }}
            </div>
            <div class="small muted">Deutsche KFZ-Gutachterzentrale</div>
        </td>
        <td class="num" style="vertical-align: top;">
            <div class="eyebrow">Provisionsabrechnung</div>
            <div style="font-size: 12pt; color: {{ $navy700 }}; padding-top: 4pt;">{{ $invoiceNumber }}</div>
            <div class="small muted">{{ Formatter::date($issuedAt) }}</div>
        </td>
    </tr>
</table>

<div style="height: 18pt;"></div>

<table>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div class="eyebrow">Rechnungssteller</div>
            <div class="rule"></div>
            <div style="padding-top: 8pt;">
                {{ Settings::get('contact.company_name', 'DKGZ Deutsche KFZ-Gutachterzentrale') }}<br>
                {{ Settings::get('contact.street', '') }}<br>
                {{ Settings::get('contact.postal_code', '') }} {{ Settings::get('contact.city', '') }}<br>
                @if (Settings::get('contact.vat_id'))
                    <span class="small muted">USt-IdNr. {{ Settings::get('contact.vat_id') }}</span>
                @endif
            </div>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <div class="eyebrow">Rechnungsempfänger</div>
            <div class="rule"></div>
            <div style="padding-top: 8pt;">
                {{ $assessor?->company_name }}<br>
                {{ $assessor?->displayAddress() }}<br>
                @if ($assessor?->vat_id)
                    <span class="small muted">USt-IdNr. {{ $assessor->vat_id }}</span>
                @endif
            </div>
        </td>
    </tr>
</table>

<div style="height: 22pt;"></div>

<div class="eyebrow">Abgerechneter Vorgang</div>
<table class="data" style="margin-top: 8pt; border-top: 0.5pt solid {{ $gray200 }};">
    <tr>
        <td class="label">Vorgangsnummer</td>
        <td>{{ $request?->reference }}</td>
    </tr>
    <tr>
        <td class="label">Art des Gutachtens</td>
        <td>{{ $request?->serviceType?->name_de }}</td>
    </tr>
    <tr>
        <td class="label">Standort</td>
        <td>{{ $request?->locationLabel() }}</td>
    </tr>
    <tr>
        <td class="label">Abgeschlossen am</td>
        <td>{{ Formatter::date($commission->assignment?->completed_at) }}</td>
    </tr>
</table>

<div style="height: 22pt;"></div>

<div class="eyebrow">Berechnung</div>
<table class="data" style="margin-top: 8pt; border-top: 0.5pt solid {{ $gray200 }};">
    <tr>
        <td class="label">Berechnetes Honorar (netto)</td>
        <td class="num">{{ Formatter::money($commission->fee_cents) }}</td>
    </tr>
    <tr>
        <td class="label">Vermittlungsprovision</td>
        <td class="num">{{ Formatter::percent((float) $commission->rate_percent) }}</td>
    </tr>
    <tr>
        <td class="label">Ihr Anteil</td>
        <td class="num">{{ Formatter::money($commission->assessorShareCents()) }}</td>
    </tr>
</table>

<table class="total">
    <tr>
        <td>Provisionsbetrag</td>
        <td class="num">{{ Formatter::money($commission->commission_cents) }}</td>
    </tr>
</table>

<div style="height: 8pt;"></div>
<p class="small muted">
    Der Provisionssatz von {{ Formatter::percent((float) $commission->rate_percent) }} entspricht dem zum Zeitpunkt des
    Abschlusses vereinbarten Satz und bleibt für diese Abrechnung unverändert.
</p>

<div style="height: 22pt;"></div>
<div class="foot">
    {{ Settings::get('contact.company_name', 'DKGZ Deutsche KFZ-Gutachterzentrale') }} ·
    {{ Settings::get('contact.street', '') }} ·
    {{ Settings::get('contact.postal_code', '') }} {{ Settings::get('contact.city', '') }}
    @if (Settings::get('contact.phone')) · Telefon {{ Settings::get('contact.phone') }} @endif
    @if (Settings::get('contact.managing_director')) <br>Geschäftsführung: {{ Settings::get('contact.managing_director') }} @endif
    @if (Settings::get('contact.register_court')) · {{ Settings::get('contact.register_court') }} {{ Settings::get('contact.register_number') }} @endif
</div>

</body>
</html>

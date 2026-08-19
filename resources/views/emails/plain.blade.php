@php
    use App\Support\Settings;
    $strip = fn (?string $html) => trim(preg_replace('/\n{3,}/', "\n\n", html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", (string) $html)), ENT_QUOTES, 'UTF-8')));
@endphp
@if ($headline){{ $headline }}

@endif
@if ($salutation){{ $salutation }}

@endif
{{ $strip($bodyHtml) }}
@if ($reference)

{{ $referenceLabel }}: {{ $reference }}
@endif
@if (! empty($rows))

@foreach ($rows as $row)
{{ $row['k'] }}: {{ $row['v'] }}
@endforeach
@endif
@if ($maskNotice)

Kontaktdaten noch nicht sichtbar. Name, Telefon und E-Mail der anfragenden
Person werden freigegeben, sobald Sie den Auftrag annehmen.
@endif
@if ($cta && $ctaUrl)

{{ $cta }}:
{{ $ctaUrl }}
@endif
@if ($note)

{{ $note }}
@endif

Mit freundlichen Grüßen
Deutsche KFZ-Gutachterzentrale

--
{{ Settings::get('contact.company_name', 'DKGZ Deutsche KFZ-Gutachterzentrale') }}
{{ trim(Settings::get('contact.street', '').', '.Settings::get('contact.postal_code', '').' '.Settings::get('contact.city', ''), ', ') }}
Telefon {{ Settings::get('contact.phone', '') }}

Impressum: {{ url('/impressum') }}
Datenschutz: {{ url('/datenschutz') }}
@if ($footnote)

{{ $footnote }}
@endif

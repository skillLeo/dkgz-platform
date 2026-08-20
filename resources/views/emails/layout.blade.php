@php
    use App\Support\Settings;

    $navy900 = '#0A1628';
    $navy700 = '#14294A';
    $navy500 = '#27508B';
    $accent = '#B08A2E';
    $gray800 = '#2E3A4C';
    $gray600 = '#5B687C';
    $gray400 = '#9AA5B4';
    $gray200 = '#E3E7EE';
    $gray100 = '#F1F3F7';
    $gray50 = '#F8F9FB';
    $navy100 = '#E8EDF5';

    $sans = "'IBM Plex Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif";
    $mono = "'IBM Plex Mono', 'Courier New', monospace";

    $company = Settings::get('contact.company_name', 'DKGZ Deutsche KFZ-Gutachterzentrale');
    $street = Settings::get('contact.street', '');
    $plz = Settings::get('contact.postal_code', '');
    $city = Settings::get('contact.city', '');
    $phone = Settings::get('contact.phone', '');
    $hours = Settings::get('contact.office_hours', '');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $eyebrow ?? 'DKGZ' }}</title>
</head>
{{-- Web fonts are named but never required: Arial/Helvetica carry the mail if
     IBM Plex is not installed, and nothing is fetched from a CDN. --}}
<body style="margin:0;padding:0;background:{{ $gray100 }};font-family:{{ $sans }};font-size:15px;line-height:1.6;color:{{ $gray800 }};-webkit-text-size-adjust:100%;">

<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader }}</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $gray100 }};">
    <tr>
        <td align="center" style="padding:24px 12px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background:#FFFFFF;border:1px solid {{ $gray200 }};">

                {{-- Kopfband --}}
                <tr>
                    <td style="background:{{ $navy900 }};padding:20px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="left" style="font-size:19px;font-weight:700;letter-spacing:-0.022em;color:#FFFFFF;line-height:1;">
                                    {{ Settings::get('branding.platform_name', 'DKGZ') }}
                                    <span style="display:inline-block;padding-left:10px;font-size:8px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.72);line-height:1.4;">
                                        Deutsche<br />KFZ-Gutachterzentrale
                                    </span>
                                </td>
                                <td align="right" width="32">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                        <td width="11" height="3" style="background:#000000;font-size:0;line-height:0;">&nbsp;</td>
                                        <td width="11" height="3" style="background:#DD0000;font-size:0;line-height:0;">&nbsp;</td>
                                        <td width="10" height="3" style="background:#FFCE00;font-size:0;line-height:0;">&nbsp;</td>
                                    </tr></table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Inhalt --}}
                <tr>
                    <td style="padding:32px;">
                        @if ($eyebrow)
                            <div style="font-size:11.5px;font-weight:600;letter-spacing:0.09em;text-transform:uppercase;color:{{ $accent }};">{{ $eyebrow }}</div>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;"><tr>
                                <td width="24" height="2" style="background:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td>
                            </tr></table>
                        @endif

                        @if ($headline)
                            <h1 style="margin:20px 0 0;font-size:24px;line-height:1.22;font-weight:600;letter-spacing:-0.016em;color:{{ $navy700 }};">{{ $headline }}</h1>
                        @endif

                        @if ($salutation)
                            <p style="margin:16px 0 0;font-size:15px;line-height:1.7;color:{{ $gray800 }};">{{ $salutation }}</p>
                        @endif

                        {{-- Admin-editable body --}}
                        @if ($bodyHtml)
                            <div style="font-size:15px;line-height:1.7;color:{{ $gray800 }};">{!! $bodyHtml !!}</div>
                        @else
                            @includeIf('emails.fallback.'.str_replace('-', '_', $templateKey), ['vars' => $vars])
                        @endif

                        @if ($reference)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;border:1px solid {{ $navy700 }};border-radius:3px;">
                                <tr><td style="padding:16px;">
                                    <div style="font-size:11.5px;font-weight:600;letter-spacing:0.09em;text-transform:uppercase;color:{{ $gray600 }};">{{ $referenceLabel }}</div>
                                    <div style="padding-top:6px;font-family:{{ $mono }};font-size:20px;color:{{ $navy700 }};">{{ $reference }}</div>
                                </td></tr>
                            </table>
                        @endif


                                {{-- The assessor's portrait, where one was supplied.
                                     Table-based and with a hard width so Outlook
                                     lays it out; initials stand in otherwise, so
                                     the block is never a broken image icon. --}}
                                @if ($portrait || $portraitInitials)
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="padding-bottom:16px;">
                                        <tr>
                                            <td width="64" style="width:64px;vertical-align:middle;">
                                                @if ($portrait)
                                                    <img src="{{ $portrait }}" width="64" height="64" alt="Ihr Sachverständiger"
                                                         style="display:block;width:64px;height:64px;border-radius:32px;border:1px solid {{ $gray200 }};" />
                                                @else
                                                    <div style="width:64px;height:64px;border-radius:32px;background:{{ $navy100 }};color:{{ $navy700 }};font-size:20px;font-weight:600;line-height:64px;text-align:center;">{{ $portraitInitials }}</div>
                                                @endif

                        @if (! empty($rows))
                            <div style="padding-top:24px;">
                                            </td>
                                        </tr>
                                    </table>
                                @endif

                                @if ($dataTitle)
                                    <div style="padding-bottom:12px;font-size:11.5px;font-weight:600;letter-spacing:0.09em;text-transform:uppercase;color:{{ $gray600 }};">{{ $dataTitle }}</div>
                                @endif
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid {{ $gray200 }};">
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td width="42%" style="padding:11px 12px 11px 0;border-bottom:1px solid {{ $gray100 }};font-size:13.5px;line-height:1.5;color:{{ $gray600 }};vertical-align:top;">{{ $row['k'] }}</td>
                                            <td style="padding:11px 0;border-bottom:1px solid {{ $gray100 }};font-size:13.5px;line-height:1.5;color:{{ $gray800 }};@if (! empty($row['mono'])) font-family:{{ $mono }}; @endif">{{ $row['v'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endif

                        @if ($maskNotice)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;border:1px solid {{ $gray200 }};border-radius:3px;background:{{ $gray50 }};">
                                <tr><td style="padding:16px;">
                                    <div style="font-size:13.5px;font-weight:500;color:{{ $gray800 }};">Kontaktdaten noch nicht sichtbar</div>
                                    <p style="margin:8px 0 0;font-size:13.5px;line-height:1.6;color:{{ $gray600 }};">Name, Telefon und E-Mail der anfragenden Person werden freigegeben, sobald Sie den Auftrag annehmen.</p>
                                </td></tr>
                            </table>
                        @endif

                        @if ($quote)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;border:1px solid {{ $gray200 }};border-left:3px solid {{ $navy700 }};border-radius:3px;background:{{ $gray50 }};">
                                <tr><td style="padding:16px 20px;">
                                    <p style="margin:0;font-size:15px;line-height:1.7;color:{{ $gray800 }};">{{ $quote }}</p>
                                    @if ($quoteBy)
                                        <div style="padding-top:10px;font-family:{{ $mono }};font-size:12.5px;color:{{ $gray400 }};">{{ $quoteBy }}</div>
                                    @endif
                                </td></tr>
                            </table>
                        @endif

                        @if ($cta && $ctaUrl)
                            <div style="padding-top:28px;">
                                <a href="{{ $ctaUrl }}" style="display:inline-block;padding:13px 24px;background:{{ $navy700 }};color:#FFFFFF;border-radius:3px;font-size:15px;font-weight:500;text-decoration:none;">{{ $cta }}</a>
                                <div style="padding-top:12px;font-family:{{ $mono }};font-size:12.5px;color:{{ $navy500 }};word-break:break-all;">{{ $ctaUrl }}</div>
                            </div>
                        @endif

                        @if ($note)
                            <p style="margin:28px 0 0;padding-top:16px;border-top:1px solid {{ $gray200 }};font-size:13.5px;line-height:1.6;color:{{ $gray600 }};">{{ $note }}</p>
                        @endif

                        <p style="margin:24px 0 0;font-size:15px;line-height:1.7;color:{{ $gray800 }};">Mit freundlichen Grüßen<br />Deutsche KFZ-Gutachterzentrale</p>
                    </td>
                </tr>

                {{-- Fuß: die Rechtszeile steht in jeder Mail --}}
                <tr>
                    <td style="background:{{ $gray50 }};border-top:1px solid {{ $gray200 }};padding:20px 32px;">
                        <div style="font-size:12.5px;line-height:1.7;color:{{ $gray600 }};">
                            {{-- Built in PHP rather than chained inline directives: an
                                 @endif immediately followed by @if compiled to invalid
                                 code and only failed when this template actually rendered. --}}
                            @php
                                $legalLine = collect([$company, $street, trim($plz.' '.$city)])
                                    ->filter()
                                    ->implode(' · ');
                                $contactLine = collect([$phone ? 'Telefon '.$phone : null, $hours])
                                    ->filter()
                                    ->implode(' · ');
                            @endphp
                            {{ $legalLine }}
                            @if ($contactLine)
                                <br />{{ $contactLine }}
                            @endif
                        </div>
                        <div style="padding-top:10px;font-size:12.5px;color:{{ $gray600 }};">
                            <a href="{{ url('/impressum') }}" style="color:{{ $navy700 }};text-decoration:none;">Impressum</a> ·
                            <a href="{{ url('/datenschutz') }}" style="color:{{ $navy700 }};text-decoration:none;">Datenschutzerklärung</a> ·
                            <a href="{{ url('/agb') }}" style="color:{{ $navy700 }};text-decoration:none;">AGB</a>
                        </div>
                        @if ($footnote)
                            <div style="padding-top:10px;font-size:12.5px;line-height:1.6;color:{{ $gray400 }};">{{ $footnote }}</div>
                        @endif
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>

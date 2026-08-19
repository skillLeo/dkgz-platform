<?php

namespace App\Support;

/**
 * Admin-overridable brand values, resolved into the CSS custom properties the
 * root layout injects. Changing a colour in the admin rethemes the site with
 * no rebuild, because every component references the token, not a literal.
 */
class Branding
{
    /**
     * The Foundations colour ramp. Keys are the setting suffix, values are the
     * CSS custom property and the built-in default from DESIGN_TOKENS.md.
     *
     * @return array<string, array{property: string, default: string, label: string}>
     */
    public static function tokens(): array
    {
        return [
            'navy_900' => ['property' => '--color-navy-900', 'default' => '#0A1628', 'label' => 'Navy 900 · dunkelste Fläche'],
            'navy_800' => ['property' => '--color-navy-800', 'default' => '#0E1E36', 'label' => 'Navy 800 · Button gedrückt'],
            'navy_700' => ['property' => '--color-navy-700', 'default' => '#14294A', 'label' => 'Navy 700 · Primärfarbe'],
            'navy_600' => ['property' => '--color-navy-600', 'default' => '#1C3A66', 'label' => 'Navy 600'],
            'navy_500' => ['property' => '--color-navy-500', 'default' => '#27508B', 'label' => 'Navy 500 · Hover und Fokus'],
            'navy_100' => ['property' => '--color-navy-100', 'default' => '#E8EDF5', 'label' => 'Navy 100 · Markierung'],
            'gray_50' => ['property' => '--color-gray-50', 'default' => '#F8F9FB', 'label' => 'Grau 50'],
            'gray_100' => ['property' => '--color-gray-100', 'default' => '#F1F3F7', 'label' => 'Grau 100 · Seitenhintergrund'],
            'gray_200' => ['property' => '--color-gray-200', 'default' => '#E3E7EE', 'label' => 'Grau 200 · Linien'],
            'gray_300' => ['property' => '--color-gray-300', 'default' => '#CBD2DD', 'label' => 'Grau 300 · Feldrahmen'],
            'gray_400' => ['property' => '--color-gray-400', 'default' => '#9AA5B4', 'label' => 'Grau 400 · Platzhalter'],
            'gray_600' => ['property' => '--color-gray-600', 'default' => '#5B687C', 'label' => 'Grau 600 · Sekundärtext'],
            'gray_800' => ['property' => '--color-gray-800', 'default' => '#2E3A4C', 'label' => 'Grau 800 · Fließtext'],
            'accent' => ['property' => '--dkgz-accent', 'default' => '#B08A2E', 'label' => 'Akzent · Gold'],
            'danger' => ['property' => '--color-danger', 'default' => '#A32318', 'label' => 'Fehler'],
            'success' => ['property' => '--color-success', 'default' => '#1B6E48', 'label' => 'Erfolg'],
            'warning' => ['property' => '--color-warning', 'default' => '#9C6F15', 'label' => 'Warnung'],
        ];
    }

    /**
     * Only overrides that actually differ from the default are emitted, so the
     * inline style block stays empty on a stock installation.
     */
    public static function cssCustomProperties(): string
    {
        $declarations = [];

        foreach (self::tokens() as $key => $token) {
            $value = Settings::get("branding.color_{$key}");

            if (blank($value) || strcasecmp((string) $value, $token['default']) === 0) {
                continue;
            }

            if (! self::isHexColour((string) $value)) {
                continue;
            }

            $declarations[] = "{$token['property']}:{$value}";
        }

        return implode(';', $declarations);
    }

    /** @return array<string, mixed> */
    public static function forInertia(): array
    {
        return [
            'platform_name' => Settings::get('branding.platform_name', 'DKGZ'),
            'platform_subtitle' => Settings::get('branding.platform_subtitle', 'Deutsche KFZ-Gutachterzentrale'),
            'logo_light' => SafeStorage::url(Settings::get('branding.logo_light')),
            'logo_dark' => SafeStorage::url(Settings::get('branding.logo_dark')),
            'seal' => SafeStorage::url(Settings::get('branding.seal')),
            'favicon' => SafeStorage::url(Settings::get('branding.favicon')),
        ];
    }

    /** Guards against anything but a literal hex colour reaching the stylesheet. */
    public static function isHexColour(string $value): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }
}

<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Applies admin-configured mail credentials over the .env defaults at runtime.
 *
 * Reads are cached for an hour by App\Support\Settings and busted whenever the
 * integrations group is saved, so a shared host is not hit with an extra query
 * on every request.
 */
class DynamicConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // During installation the settings table does not exist yet.
        if (! Settings::isAvailable()) {
            return;
        }

        $host = Settings::get('integrations.smtp_host');

        // No SMTP configured: stay on the .env mailer and let the admin banner
        // tell the operator that outgoing mail is not set up yet.
        if (blank($host)) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', (int) Settings::get('integrations.smtp_port', 587));
        Config::set('mail.mailers.smtp.username', Settings::get('integrations.smtp_username'));
        Config::set('mail.mailers.smtp.password', Settings::get('integrations.smtp_password'));

        $encryption = Settings::get('integrations.smtp_encryption', 'tls');
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.scheme', $encryption === 'ssl' ? 'smtps' : 'smtp');

        if (filled($from = Settings::get('email.from_address'))) {
            Config::set('mail.from.address', $from);
        }

        if (filled($fromName = Settings::get('email.from_name'))) {
            Config::set('mail.from.name', $fromName);
        }

        if (filled($replyTo = Settings::get('email.reply_to'))) {
            Config::set('mail.reply_to', ['address' => $replyTo, 'name' => Settings::get('email.from_name', '')]);
        }
    }
}

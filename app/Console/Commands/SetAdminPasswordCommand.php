<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sets an administrator password and prints it once.
 *
 * The account is flagged to force a change at next sign-in, because a password
 * that has been printed to a console has almost certainly also been pasted into
 * a chat window or captured in a deployment log.
 */
class SetAdminPasswordCommand extends Command
{
    protected $signature = 'dkgz:set-admin-password
        {email? : Adresse des Kontos, sonst der erste Administrator}
        {--password= : Eigenes Passwort statt eines erzeugten}';

    protected $description = 'Setzt ein Administrator-Passwort und erzwingt die Änderung bei der nächsten Anmeldung.';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = $email
            ? User::where('email', $email)->first()
            : User::role(['super_admin', 'admin'])->orderBy('id')->first();

        if ($user === null) {
            $this->error($email
                ? "Es gibt kein Konto mit der Adresse {$email}."
                : 'Es ist kein Administrator vorhanden.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(16, symbols: false);

        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
            'is_active' => true,
        ])->save();

        $this->newLine();
        $this->info('Passwort gesetzt.');
        $this->line('  Adresse:  '.$user->email);
        $this->line('  Passwort: '.$password);
        $this->newLine();
        $this->warn('Bei der nächsten Anmeldung muss ein eigenes Passwort vergeben werden.');
        $this->newLine();

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Everything a live installation needs and nothing it does not. Safe to re-run:
 * every child seeder upserts, and no operator-entered value is overwritten.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            ServiceTypeSeeder::class,
            PostalCodeSeeder::class,
            EmailTemplateSeeder::class,
            ContentBlockSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
        ]);

        $this->createSuperAdmin();
    }

    private function createSuperAdmin(): void
    {
        $email = env('DKGZ_ADMIN_EMAIL', 'admin@dkgz.de');

        $user = User::withTrashed()->firstWhere('email', $email);

        if ($user !== null) {
            $user->restore();
            $user->syncRoles(['super_admin']);

            return;
        }

        // A one-off password is generated unless the deployment supplies one,
        // so no installation ever ships with a known default credential.
        $password = env('DKGZ_ADMIN_PASSWORD');
        $generated = $password === null;
        $password ??= 'Dkgz!'.bin2hex(random_bytes(6));

        $user = User::create([
            'first_name' => 'DKGZ',
            'last_name' => 'Administration',
            'name' => 'DKGZ Administration',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'locale' => 'de',
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');

        if ($generated) {
            $this->command?->newLine();
            $this->command?->warn('Super-Admin angelegt: '.$email);
            $this->command?->warn('Einmal-Passwort:      '.$password);
            $this->command?->warn('Bitte sofort nach der ersten Anmeldung ändern.');
            $this->command?->newLine();
        }
    }
}

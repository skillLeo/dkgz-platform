<?php

use App\Models\Assessor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    RateLimiter::clear('');
});

function assessorUser(string $status = Assessor::STATUS_APPROVED): User
{
    $user = User::factory()->create(['password' => Hash::make('Gutachten2026!')]);
    $user->assignRole('assessor');
    Assessor::factory()->create(['user_id' => $user->id, 'approval_status' => $status]);

    return $user->fresh();
}

describe('login screen', function () {
    it('renders the partner login', function () {
        $this->get('/anmelden')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Anmelden')->where('admin', false));
    });

    it('renders the separate admin login', function () {
        $this->get('/admin/anmelden')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Anmelden')->where('admin', true));
    });
});

describe('signing in', function () {
    it('sends an assessor to the portal', function () {
        $user = assessorUser();

        $this->post('/anmelden', ['email' => $user->email, 'password' => 'Gutachten2026!'])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('sends staff to the admin panel', function () {
        $user = User::factory()->create(['password' => Hash::make('Gutachten2026!')]);
        $user->assignRole('admin');

        $this->post('/admin/anmelden', ['email' => $user->email, 'password' => 'Gutachten2026!'])
            ->assertRedirect(route('admin.dashboard'));
    });

    it('records the login time and address', function () {
        $user = assessorUser();

        $this->post('/anmelden', ['email' => $user->email, 'password' => 'Gutachten2026!']);

        expect($user->fresh()->last_login_at)->not->toBeNull()
            ->and($user->fresh()->last_login_ip)->not->toBeNull();
    });

    it('rejects a wrong password with a German message', function () {
        $user = assessorUser();

        $this->post('/anmelden', ['email' => $user->email, 'password' => 'falsch'])
            ->assertSessionHasErrors(['email' => 'E-Mail-Adresse oder Passwort ist nicht korrekt.']);

        $this->assertGuest();
    });

    it('turns away a deactivated account and says so plainly', function () {
        $user = assessorUser();
        $user->update(['is_active' => false]);

        $this->post('/anmelden', ['email' => $user->email, 'password' => 'Gutachten2026!'])
            ->assertSessionHasErrors(['email' => 'Dieses Konto ist deaktiviert. Bitte wenden Sie sich an die Administration.']);

        $this->assertGuest();
    });

    it('throttles after five attempts on the same address', function () {
        $user = assessorUser();

        foreach (range(1, 5) as $ignored) {
            $this->post('/anmelden', ['email' => $user->email, 'password' => 'falsch']);
        }

        $this->post('/anmelden', ['email' => $user->email, 'password' => 'falsch'])
            ->assertSessionHasErrorsIn('default', ['email']);

        expect(session('errors')->first('email'))->toContain('Zu viele Anmeldeversuche');
    });
});

describe('signing out', function () {
    it('ends the session', function () {
        $user = assessorUser();

        $this->actingAs($user)->post('/abmelden')->assertRedirect('/');

        $this->assertGuest();
    });
});

describe('the approval gate', function () {
    it('holds a pending partner on the waiting screen', function () {
        $this->actingAs(assessorUser(Assessor::STATUS_PENDING))
            ->get('/portal')
            ->assertRedirect(route('registration.pending'));
    });

    it('sends a rejected partner to the rejection screen', function () {
        $this->actingAs(assessorUser(Assessor::STATUS_REJECTED))
            ->get('/portal')
            ->assertRedirect(route('registration.rejected'));
    });

    it('sends a suspended partner to the blocked screen', function () {
        $this->actingAs(assessorUser(Assessor::STATUS_SUSPENDED))
            ->get('/portal')
            ->assertRedirect(route('account.blocked'));
    });

    it('lets an approved partner through', function () {
        $this->actingAs(assessorUser())->get('/portal')->assertOk();
    });

    it('refuses a staff account that has no assessor profile', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)->get('/portal')->assertForbidden();
    });
});

describe('password reset', function () {
    it('never reveals whether an address exists', function () {
        $known = assessorUser();

        $this->post('/passwort-vergessen', ['email' => $known->email])->assertSessionHas('status');
        $this->post('/passwort-vergessen', ['email' => 'niemand@example.test'])->assertSessionHas('status');
    });

    it('requires a strong new password', function () {
        $user = assessorUser();
        $token = Password::createToken($user);

        $this->post('/passwort-zuruecksetzen', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'kurz',
            'password_confirmation' => 'kurz',
        ])->assertSessionHasErrors('password');
    });

    it('changes the password with a valid token', function () {
        $user = assessorUser();
        $token = Password::createToken($user);

        $this->post('/passwort-zuruecksetzen', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NeuesPasswort2026!',
            'password_confirmation' => 'NeuesPasswort2026!',
        ])->assertRedirect(route('login'));

        expect(Hash::check('NeuesPasswort2026!', $user->fresh()->password))->toBeTrue();
    });
});

describe('guest-only routes', function () {
    it('keeps a signed-in user off the login screen', function () {
        $this->actingAs(assessorUser())->get('/anmelden')->assertRedirect();
    });
});

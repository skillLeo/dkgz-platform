<?php

use App\Models\Assessor;
use App\Models\Assignment;
use App\Models\Commission;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function userWithRole(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

it('seeds every role and permission', function () {
    expect(Role::count())->toBe(6)
        ->and(Permission::count())->toBe(count(Permissions::all()));
});

it('gives super_admin every ability through the Gate', function () {
    $user = userWithRole('super_admin');

    foreach (Permissions::all() as $permission) {
        expect($user->can($permission))->toBeTrue();
    }
});

it('withholds role and integration management from admin', function () {
    $user = userWithRole('admin');

    expect($user->can('roles.manage'))->toBeFalse()
        ->and($user->can('integrations.manage'))->toBeFalse()
        ->and($user->can('requests.view'))->toBeTrue()
        ->and($user->can('commissions.settle'))->toBeTrue();
});

it('limits manager to operations and keeps settings away', function () {
    $user = userWithRole('manager');

    expect($user->can('assessors.approve'))->toBeTrue()
        ->and($user->can('commissions.invoice'))->toBeTrue()
        ->and($user->can('settings.edit'))->toBeFalse()
        ->and($user->can('users.create'))->toBeFalse();
});

it('keeps support read-only', function () {
    $user = userWithRole('support');

    expect($user->can('requests.view'))->toBeTrue()
        ->and($user->can('assignments.view'))->toBeTrue()
        ->and($user->can('requests.delete'))->toBeFalse()
        ->and($user->can('assessors.approve'))->toBeFalse()
        ->and($user->can('commissions.settle'))->toBeFalse();
});

it('limits content_editor to content and branding', function () {
    $user = userWithRole('content_editor');

    expect($user->can('content.edit'))->toBeTrue()
        ->and($user->can('pages.manage'))->toBeTrue()
        ->and($user->can('emails.edit'))->toBeTrue()
        ->and($user->can('branding.edit'))->toBeTrue()
        ->and($user->can('requests.view'))->toBeFalse()
        ->and($user->can('commissions.view'))->toBeFalse();
});

it('gives an assessor no admin permission at all', function () {
    $user = userWithRole('assessor');

    foreach (Permissions::all() as $permission) {
        expect($user->can($permission))->toBeFalse();
    }
});

it('revokes every ability from a deactivated account', function () {
    $user = userWithRole('admin');
    expect($user->can('requests.view'))->toBeTrue();

    $user->update(['is_active' => false]);
    $user->refresh();

    expect($user->can('requests.view'))->toBeFalse();
});

it('revokes even super_admin abilities when the account is deactivated', function () {
    $user = userWithRole('super_admin');
    $user->update(['is_active' => false]);
    $user->refresh();

    expect($user->can('settings.edit'))->toBeFalse();
});

describe('assignment ownership', function () {
    it('lets an assessor see only their own assignment', function () {
        $mine = Assignment::factory()->create();
        $theirs = Assignment::factory()->create();

        $owner = User::factory()->create()->assignRole('assessor');
        $mine->assessor->update(['user_id' => $owner->id]);
        $owner->refresh();

        expect($owner->can('view', $mine))->toBeTrue()
            ->and($owner->can('view', $theirs))->toBeFalse();
    });

    it('lets staff with assignments.view see any assignment', function () {
        $assignment = Assignment::factory()->create();

        expect(userWithRole('support')->can('view', $assignment))->toBeTrue();
    });
});

describe('commission visibility', function () {
    it('lets an assessor read their own commission but never edit it', function () {
        $commission = Commission::factory()->create();
        $owner = User::factory()->create()->assignRole('assessor');
        $commission->assessor->update(['user_id' => $owner->id]);
        $owner->refresh();

        expect($owner->can('view', $commission))->toBeTrue()
            ->and($owner->can('update', $commission))->toBeFalse()
            ->and($owner->can('settle', $commission))->toBeFalse();
    });
});

describe('protected roles', function () {
    it('refuses to let anyone edit or delete super_admin', function () {
        $actor = userWithRole('super_admin');
        $role = Role::findByName('super_admin');

        // Gate::before grants super_admin everything, so the policy is checked
        // directly here: the protection is structural, not permission-based.
        expect(app(RolePolicy::class)->update($actor, $role))->toBeFalse()
            ->and(app(RolePolicy::class)->delete($actor, $role))->toBeFalse();
    });

    it('refuses to delete a role that still has holders', function () {
        $actor = userWithRole('super_admin');
        userWithRole('manager');
        $role = Role::findByName('manager');

        expect(app(RolePolicy::class)->delete($actor, $role))->toBeFalse();
    });
});

describe('user deletion', function () {
    it('never lets a user delete themselves', function () {
        $user = userWithRole('super_admin');

        expect(app(UserPolicy::class)->delete($user, $user))->toBeFalse();
    });

    it('protects the last super_admin', function () {
        $only = userWithRole('super_admin');
        $other = userWithRole('admin');

        expect(app(UserPolicy::class)->delete($other, $only))->toBeFalse();
    });
});

describe('assessor approval transitions', function () {
    it('only allows approval from the pending state', function () {
        $manager = userWithRole('manager');
        $pending = Assessor::factory()->pending()->create();
        $approved = Assessor::factory()->create();

        expect($manager->can('approve', $pending))->toBeTrue()
            ->and($manager->can('approve', $approved))->toBeFalse()
            ->and($manager->can('suspend', $approved))->toBeTrue()
            ->and($manager->can('suspend', $pending))->toBeFalse();
    });
});

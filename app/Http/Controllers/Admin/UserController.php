<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'assessor'))
            ->when($request->filled('suche'), fn ($q) => $q->where(function ($inner) use ($request) {
                $term = '%'.$request->string('suche')->toString().'%';
                $inner->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->filled('rolle'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->string('rolle'))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Benutzer', [
            'users' => $users->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->fullName(),
                'email' => $u->email,
                'roles' => $u->roles->pluck('name'),
                'is_active' => $u->is_active,
                'last_login_at' => $u->last_login_at,
                'two_factor' => $u->hasTwoFactorEnabled(),
            ]),
            'filters' => $request->only(['suche', 'rolle']),
            'roles' => Role::where('name', '!=', 'assessor')->pluck('name'),
            'can' => [
                'create' => $request->user()->can('create', User::class),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', PasswordResetController::passwordRules()],
            'role' => ['required', Rule::in(Role::where('name', '!=', 'assessor')->pluck('name'))],
        ], [], [
            'first_name' => 'der Vorname',
            'last_name' => 'der Nachname',
            'email' => 'die E-Mail-Adresse',
            'password' => 'das Passwort',
            'role' => 'die Rolle',
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'locale' => 'de',
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        return back()->with('success', 'Der Benutzer wurde angelegt.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['nullable', Rule::in(Role::where('name', '!=', 'assessor')->pluck('name'))],
            'is_active' => ['boolean'],
        ]);

        // Nobody may lock themselves out of their own account.
        if ($request->user()->is($user) && $request->has('is_active') && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'Sie können Ihr eigenes Konto nicht deaktivieren.']);
        }

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (filled($data['role'] ?? null) && $request->user()->can('roles.manage')) {
            $user->syncRoles([$data['role']]);
        }

        return back()->with('success', 'Der Benutzer wurde gespeichert.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'Der Benutzer wurde gelöscht.');
    }

    // ---- Roles and the permission matrix ---------------------------------

    public function roles(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Admin/Rollen', [
            'roles' => Role::with('permissions')->withCount('users')->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => Permissions::roles()[$role->name]['label'] ?? $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => $role->users_count,
                    'protected' => in_array($role->name, Permissions::PROTECTED_ROLES, true),
                    'can_edit' => $request->user()->can('update', $role),
                    'can_delete' => $request->user()->can('delete', $role),
                ]),
            'groups' => Permissions::groups(),
            'canManage' => $request->user()->can('roles.manage'),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z_]+$/', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ], [
            'name.regex' => 'Der Name darf nur Kleinbuchstaben und Unterstriche enthalten.',
        ], ['name' => 'der Name']);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Die Rolle wurde angelegt.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ]);

        $permissions = $data['permissions'] ?? [];

        // A user may not strip their own ability to manage roles — that is the
        // one change nothing inside the interface could undo.
        if ($request->user()->hasRole($role->name)
            && ! in_array('roles.manage', $permissions, true)
            && $role->hasPermissionTo('roles.manage')) {
            return back()->withErrors([
                'permissions' => 'Sie können sich die Rollenverwaltung nicht selbst entziehen.',
            ]);
        }

        $role->syncPermissions($permissions);

        return back()->with('success', 'Die Rolle wurde gespeichert.');
    }

    public function destroyRole(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return back()->with('success', 'Die Rolle wurde gelöscht.');
    }

    /** Seeds any permission that is defined in code but missing in the table. */
    public function syncPermissions(Request $request): RedirectResponse
    {
        $this->authorize('roles.manage');

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        return back()->with('success', 'Die Berechtigungen wurden abgeglichen.');
    }
}

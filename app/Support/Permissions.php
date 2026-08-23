<?php

namespace App\Support;

/**
 * The complete permission vocabulary, grouped for the admin permission matrix.
 *
 * Routes are gated by permission, never by role name. Roles are only bundles of
 * these strings, and an admin may recompose them freely — which is why nothing
 * in the codebase is allowed to test for a role except the assessor portal
 * boundary, where "assessor" is a structural fact (a user with a profile row).
 */
class Permissions
{
    /** @return array<string, array{label: string, permissions: array<string, string>}> */
    public static function groups(): array
    {
        return [
            'requests' => [
                'label' => 'Anfragen',
                'permissions' => [
                    'requests.view' => 'Anfragen einsehen',
                    'requests.create' => 'Anfragen telefonisch aufnehmen',
                    'requests.export' => 'Anfragen exportieren',
                    'requests.delete' => 'Anfragen löschen',
                    'requests.reassign' => 'Anfragen erneut vermitteln',
                ],
            ],
            'assignments' => [
                'label' => 'Aufträge',
                'permissions' => [
                    'assignments.view' => 'Aufträge einsehen',
                    'assignments.edit' => 'Aufträge bearbeiten',
                    'assignments.cancel' => 'Aufträge stornieren',
                    'assignments.export' => 'Aufträge exportieren',
                ],
            ],
            'assessors' => [
                'label' => 'Sachverständige',
                'permissions' => [
                    'assessors.view' => 'Sachverständige einsehen',
                    'assessors.create' => 'Sachverständige anlegen',
                    'assessors.edit' => 'Sachverständige bearbeiten',
                    'assessors.approve' => 'Sachverständige freigeben',
                    'assessors.reject' => 'Sachverständige ablehnen',
                    'assessors.suspend' => 'Sachverständige sperren',
                    'assessors.delete' => 'Sachverständige löschen',
                    'assessors.export' => 'Sachverständige exportieren',
                ],
            ],
            'invitations' => [
                'label' => 'Einladungen',
                'permissions' => [
                    'invitations.view' => 'Einladungen einsehen',
                    'invitations.create' => 'Einladungen versenden',
                    'invitations.revoke' => 'Einladungen zurückziehen',
                ],
            ],
            'commissions' => [
                'label' => 'Provisionen',
                'permissions' => [
                    'commissions.view' => 'Provisionen einsehen',
                    'commissions.edit' => 'Provisionen bearbeiten',
                    'commissions.settle' => 'Provisionen als beglichen markieren',
                    'commissions.waive' => 'Provisionen erlassen',
                    'commissions.invoice' => 'Provisionsrechnungen erzeugen',
                    'commissions.export' => 'Provisionen exportieren',
                ],
            ],
            'content' => [
                'label' => 'Inhalte',
                'permissions' => [
                    'content.view' => 'Inhalte einsehen',
                    'content.edit' => 'Inhalte bearbeiten',
                    'pages.manage' => 'Seiten verwalten',
                    'faqs.manage' => 'FAQ verwalten',
                ],
            ],
            'emails' => [
                'label' => 'E-Mail-Vorlagen',
                'permissions' => [
                    'emails.view' => 'E-Mail-Vorlagen einsehen',
                    'emails.edit' => 'E-Mail-Vorlagen bearbeiten',
                    'emails.test' => 'Testmail versenden',
                ],
            ],
            'settings' => [
                'label' => 'Einstellungen',
                'permissions' => [
                    'settings.view' => 'Einstellungen einsehen',
                    'settings.edit' => 'Einstellungen bearbeiten',
                    'branding.edit' => 'Erscheinungsbild bearbeiten',
                    'integrations.manage' => 'Integrationen verwalten',
                    'servicetypes.manage' => 'Leistungsarten verwalten',
                ],
            ],
            'users' => [
                'label' => 'Benutzer und Rollen',
                'permissions' => [
                    'users.view' => 'Benutzer einsehen',
                    'users.create' => 'Benutzer anlegen',
                    'users.edit' => 'Benutzer bearbeiten',
                    'users.delete' => 'Benutzer löschen',
                    'roles.view' => 'Rollen einsehen',
                    'roles.manage' => 'Rollen verwalten',
                ],
            ],
            'logs' => [
                'label' => 'Protokoll',
                'permissions' => [
                    'logs.view' => 'Protokoll einsehen',
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            foreach (array_keys($group['permissions']) as $permission) {
                $names[] = $permission;
            }
        }

        return $names;
    }

    /** German label for a single permission, used in the matrix and the log. */
    public static function label(string $permission): string
    {
        foreach (self::groups() as $group) {
            if (isset($group['permissions'][$permission])) {
                return $group['permissions'][$permission];
            }
        }

        return $permission;
    }

    /** @return array<string, array{label: string, permissions: list<string>}> */
    public static function roles(): array
    {
        return [
            'super_admin' => [
                'label' => 'Systemadministration',
                // Everything, including roles and integrations. Undeletable.
                'permissions' => self::all(),
            ],
            'admin' => [
                'label' => 'Administration',
                // Everything operational; not roles, not integrations.
                'permissions' => array_values(array_diff(self::all(), [
                    'roles.manage', 'integrations.manage',
                ])),
            ],
            'manager' => [
                'label' => 'Vermittlung',
                'permissions' => [
                    'requests.view', 'requests.create', 'requests.export', 'requests.reassign',
                    'assignments.view', 'assignments.edit', 'assignments.cancel', 'assignments.export',
                    'assessors.view', 'assessors.create', 'assessors.edit',
                    'assessors.approve', 'assessors.reject', 'assessors.suspend', 'assessors.export',
                    'invitations.view', 'invitations.create', 'invitations.revoke',
                    'commissions.view', 'commissions.edit', 'commissions.settle',
                    'commissions.waive', 'commissions.invoice', 'commissions.export',
                    'logs.view',
                ],
            ],
            'support' => [
                'label' => 'Kundenbetreuung',
                // Read-only across operations; may add internal notes.
                'permissions' => [
                    'requests.view', 'assignments.view', 'assessors.view',
                    'invitations.view', 'commissions.view', 'logs.view',
                ],
            ],
            'content_editor' => [
                'label' => 'Redaktion',
                'permissions' => [
                    'content.view', 'content.edit', 'pages.manage', 'faqs.manage',
                    'emails.view', 'emails.edit', 'branding.edit',
                ],
            ],
            'assessor' => [
                'label' => 'Sachverständiger',
                // Portal only. Holds no admin permission at all.
                'permissions' => [],
            ],
        ];
    }

    /** Roles that may never be deleted or renamed. */
    public const PROTECTED_ROLES = ['super_admin', 'assessor'];
}

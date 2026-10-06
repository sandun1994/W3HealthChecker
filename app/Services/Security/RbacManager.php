<?php

namespace App\Services\Security;

class RbacManager
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Map of roles to explicit permitted abilities.
     */
    protected static array $permissions = [
        self::ROLE_OWNER => [
            'manage_organization',
            'manage_billing',
            'manage_members',
            'manage_websites',
            'run_scans',
            'view_reports',
            'manage_api_keys',
            'manage_monitoring',
            'export_reports',
            'manage_webhooks',
        ],
        self::ROLE_ADMIN => [
            'manage_members',
            'manage_websites',
            'run_scans',
            'view_reports',
            'manage_api_keys',
            'manage_monitoring',
            'export_reports',
            'manage_webhooks',
        ],
        self::ROLE_MEMBER => [
            'manage_websites',
            'run_scans',
            'view_reports',
            'manage_monitoring',
            'export_reports',
        ],
        self::ROLE_VIEWER => [
            'view_reports',
            'export_reports',
        ],
    ];

    /**
     * Determine if a given role has a specific permission.
     */
    public static function can(string $role, string $permission): bool
    {
        $role = strtolower(trim($role));
        $permitted = self::$permissions[$role] ?? [];

        return in_array($permission, $permitted, true);
    }

    /**
     * Get all roles list.
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_MEMBER,
            self::ROLE_VIEWER,
        ];
    }

    /**
     * Get all permissions assigned to a role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::$permissions[strtolower(trim($role))] ?? [];
    }
}

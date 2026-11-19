<?php

namespace App\Support;

/**
 * Central definition of roles and the permissions each one carries.
 * The RolesAndPermissionsSeeder reads from here, so this is the single source of truth.
 */
final class Roles
{
    public const SUPER_ADMIN = 'super_admin';
    public const GYM_ADMIN = 'gym_admin';
    public const TRAINER = 'trainer';
    public const MEMBER = 'member';

    public const ALL = [self::SUPER_ADMIN, self::GYM_ADMIN, self::TRAINER, self::MEMBER];

    public const LABELS = [
        self::SUPER_ADMIN => 'Super Admin',
        self::GYM_ADMIN => 'Gym Owner',
        self::TRAINER => 'Trainer',
        self::MEMBER => 'Member',
    ];

    /** Dashboard route name for each role. */
    public const DASHBOARDS = [
        self::SUPER_ADMIN => 'super-admin.dashboard',
        self::GYM_ADMIN => 'gym-admin.dashboard',
        self::TRAINER => 'trainer.dashboard',
        self::MEMBER => 'member.dashboard',
    ];

    public const PERMISSIONS = [
        'manage gyms',
        'manage gym admins',
        'manage trainers',
        'manage members',
        'manage plans',
        'manage payments',
        'manage attendance',
        'view reports',
        'view assigned members',
        'manage workout plans',
        'view own progress',
        'upload progress photos',
    ];

    /** Super Admin is granted everything via Gate::before, but is listed for clarity. */
    public const ROLE_PERMISSIONS = [
        self::SUPER_ADMIN => self::PERMISSIONS,
        self::GYM_ADMIN => [
            'manage trainers', 'manage members', 'manage plans',
            'manage payments', 'manage attendance', 'view reports',
        ],
        self::TRAINER => [
            'view assigned members', 'manage workout plans', 'manage attendance',
        ],
        self::MEMBER => [
            'view own progress', 'upload progress photos',
        ],
    ];

    /** Roles a given role is allowed to create / manage. */
    public static function manageableBy(string $role): array
    {
        switch ($role) {
            case self::SUPER_ADMIN:
                return self::ALL;
            case self::GYM_ADMIN:
                return [self::TRAINER, self::MEMBER];
            default:
                return [];
        }
    }

    public static function label(?string $role): string
    {
        return self::LABELS[$role] ?? 'Unknown';
    }
}

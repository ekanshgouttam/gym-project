<?php

namespace App\Support;

use App\Models\User;

/** Sidebar navigation per role, grouped into sections. */
class Navigation
{
    private const ICONS = [
        'home' => 'M3 12l9-9 9 9M5 10v10h14V10',
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'card' => 'M3 7h18v10H3zM3 11h18M7 15h3',
        'calendar' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
        'scan' => 'M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M8 12h8',
        'spark' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
        'clock' => 'M12 8v4l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'scale' => 'M3 6h18M7 6l-4 8a4 4 0 008 0L7 6zm10 0l-4 8a4 4 0 008 0l-4-8zM12 3v18',
        'ruler' => 'M3 17l14-14 4 4L7 21zM7 13l2 2M10 10l2 2M13 7l2 2',
        'dumbbell' => 'M6 7v10M3 9.5v5M18 7v10M21 9.5v5M6 12h12',
        'apple' => 'M12 3c4 3 6 6 6 9a6 6 0 01-12 0c0-3 2-6 6-9z',
        'bell' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1',
        'cog' => 'M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1.1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001 2.6 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.6-1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001-2.6 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.6-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'building' => 'M4 21V8l8-5 8 5v13M9 21v-6h6v6',
        'users' => 'M16 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1M9 10a3 3 0 100-6 3 3 0 000 6zM22 19v-1a4 4 0 00-3-3.87M16 4.13a4 4 0 010 7.75',
        'check' => 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'qr' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2z',
        'upload' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M22 11h-6',
        'inbox' => 'M4 13h4l2 3h4l2-3h4M4 13l2-8h12l2 8v6H4z',
        'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6M9 16h3',
        'chart' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'life' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 16a4 4 0 100-8 4 4 0 000 8zM5.6 5.6l3.6 3.6M14.8 14.8l3.6 3.6M18.4 5.6l-3.6 3.6M9.2 14.8l-3.6 3.6',
    ];

    /** @return array<string, array<int, array{label:string, route:string, active:bool, icon:string, badge:?int}>> */
    public static function for(User $user): array
    {
        if ($user->hasRole(Roles::MEMBER)) {
            $unread = $user->unreadNotifications()->count();

            return [
                '' => [
                    self::item('Overview', 'member.dashboard', 'home'),
                    self::item('My Profile', 'member.profile', 'user'),
                    self::item('My Membership', 'member.membership', 'card'),
                    self::item('Payments', 'member.payments', 'receipt'),
                    self::item('Attendance', 'member.attendance', 'calendar'),
                ],
                'Progress' => [
                    self::item('AI Body Scanner', 'member.scanner', 'scan', 'member.scanner*'),
                    self::item('My Transformation', 'member.transformation', 'spark'),
                    self::item('Progress History', 'member.progress', 'clock'),
                    self::item('Weight Tracking', 'member.weight', 'scale'),
                    self::item('Body Measurements', 'member.measurements', 'ruler'),
                ],
                'Plans' => [
                    self::item('Workout Plans', 'member.workouts', 'dumbbell'),
                    self::item('Diet Plans', 'member.diet', 'apple'),
                ],
                'Account' => [
                    self::item('Notifications', 'member.notifications', 'bell', 'member.notifications*', $unread ?: null),
                    self::item('Account Settings', 'member.settings', 'cog'),
                ],
            ];
        }

        if ($user->isSuperAdmin()) {
            $openTickets = \App\Models\SupportRequest::unresolved()->count();

            return [
                '' => [
                    self::item('Platform overview', 'super-admin.dashboard', 'home'),
                ],
                'Gyms' => [
                    self::item('Registered gyms', 'manage.gyms.index', 'building', 'manage.gyms.*'),
                    self::item('Subscriptions', 'super-admin.subscriptions.index', 'card', 'super-admin.subscriptions.*|super-admin.plans.*'),
                    self::item('Subscription payments', 'super-admin.payments.index', 'receipt', 'super-admin.payments.*'),
                ],
                'Insights' => [
                    self::item('Active members', 'super-admin.members', 'users'),
                    self::item('Revenue', 'super-admin.revenue', 'chart'),
                    self::item('AI usage & costs', 'super-admin.ai', 'spark'),
                ],
                'Platform' => [
                    self::item('All users', 'manage.users.index', 'user', 'manage.users.*'),
                    self::item('Membership approvals', 'manage.memberships.index', 'check', 'manage.memberships.*'),
                    self::item('Demo requests', 'super-admin.demos.index', 'calendar', 'super-admin.demos.*', \App\Models\DemoRequest::where('status', 'new')->count() ?: null),
                    self::item('Support requests', 'super-admin.support.index', 'life', 'super-admin.support.*', $openTickets ?: null),
                    self::item('Platform settings', 'super-admin.settings', 'cog'),
                ],
            ];
        }

        if ($user->hasRole(Roles::GYM_ADMIN) && $user->gym_id) {
            $gymId = $user->gym_id;
            $pending = \App\Models\Membership::where('status', 'pending')->where('gym_id', $gymId)->count();
            $followUps = \App\Models\Enquiry::where('gym_id', $gymId)->open()->whereDate('next_follow_up_on', '<=', today())->count();

            return [
                '' => [
                    self::item('Dashboard', 'gym-admin.dashboard', 'home'),
                ],
                'Members' => [
                    self::item('Members', 'manage.users.index', 'users', 'manage.users.*|manage.members.*|manage.scans.*'),
                    self::item('Enquiries', 'gym-admin.enquiries.index', 'inbox', 'gym-admin.enquiries.*', $followUps ?: null),
                    ['label' => 'Registration QR', 'route' => route('manage.gyms.qr', $gymId), 'active' => request()->routeIs('manage.gyms.qr'), 'icon' => self::ICONS['qr'], 'badge' => null],
                    self::item('Attendance', 'gym-admin.attendance.index', 'calendar', 'gym-admin.attendance.*'),
                ],
                'Money' => [
                    self::item('Plans & Fees', 'gym-admin.fees.index', 'card', 'gym-admin.fees.*'),
                    self::item('Payments', 'gym-admin.payments.index', 'receipt', 'gym-admin.payments.*'),
                    self::item('Membership approvals', 'manage.memberships.index', 'check', 'manage.memberships.*', $pending ?: null),
                ],
                'Coaching' => [
                    self::item('Trainers', 'gym-admin.trainers.index', 'user', 'gym-admin.trainers.*'),
                    ['label' => 'Workout Plans', 'route' => route('gym-admin.plans.index', ['kind' => 'workout']), 'active' => request()->routeIs('gym-admin.plans.*') && request('kind', request()->route('kind')) !== 'diet', 'icon' => self::ICONS['dumbbell'], 'badge' => null],
                    ['label' => 'Diet Plans', 'route' => route('gym-admin.plans.index', ['kind' => 'diet']), 'active' => request()->routeIs('gym-admin.plans.*') && request('kind', request()->route('kind')) === 'diet', 'icon' => self::ICONS['apple'], 'badge' => null],
                    self::item('Body Transformation', 'gym-admin.transformation', 'spark'),
                ],
                'Gym' => [
                    self::item('Reports', 'gym-admin.reports', 'chart'),
                    self::item('Settings', 'gym-admin.settings', 'cog'),
                    self::item('FlexFit support', 'manage.support.index', 'life', 'manage.support.*'),
                ],
            ];
        }

        if ($user->hasRole(Roles::TRAINER)) {
            return ['' => [
                self::item('Dashboard', 'trainer.dashboard', 'home'),
                ['label' => 'Workout Plans', 'route' => route('gym-admin.plans.index', ['kind' => 'workout']), 'active' => request()->routeIs('gym-admin.plans.*') && request('kind', request()->route('kind')) !== 'diet', 'icon' => self::ICONS['dumbbell'], 'badge' => null],
                ['label' => 'Diet Plans', 'route' => route('gym-admin.plans.index', ['kind' => 'diet']), 'active' => request()->routeIs('gym-admin.plans.*') && request('kind', request()->route('kind')) === 'diet', 'icon' => self::ICONS['apple'], 'badge' => null],
            ]];
        }

        $items = [self::item('Dashboard', $user->dashboardRoute(), 'home', '*.dashboard')];
        if ($user->can('manage gyms')) {
            $items[] = self::item('Gyms & QR codes', 'manage.gyms.index', 'building', 'manage.gyms.*');
        } elseif ($user->can('manage members') && $user->gym_id) {
            $items[] = ['label' => 'Registration QR', 'route' => route('manage.gyms.qr', $user->gym_id), 'active' => request()->routeIs('manage.gyms.qr'), 'icon' => self::ICONS['qr'], 'badge' => null];
        }
        if ($user->can('viewAny', User::class)) {
            $items[] = self::item($user->isSuperAdmin() ? 'Users' : 'Members & Trainers', 'manage.users.index', 'users', 'manage.users.*');
        }
        if ($user->can('manage members')) {
            $pending = \App\Models\Membership::where('status', 'pending')
                ->when(! $user->isSuperAdmin(), function ($q) use ($user) { $q->where('gym_id', $user->gym_id); })
                ->count();
            $items[] = self::item('Membership approvals', 'manage.memberships.index', 'check', 'manage.memberships.*', $pending ?: null);
        }
        if ($user->hasRole(Roles::GYM_ADMIN)) {
            $items[] = self::item('Add & import members', 'manage.members.create', 'upload', 'manage.members.*');
            $items[] = self::item('FlexFit support', 'manage.support.index', 'life', 'manage.support.*');
        }

        return ['' => $items];
    }

    private static function item(string $label, string $route, string $icon, ?string $pattern = null, ?int $badge = null): array
    {
        return [
            'label' => $label,
            'route' => route($route),
            'active' => request()->routeIs(...explode('|', $pattern ?? $route)),
            'icon' => self::ICONS[$icon],
            'badge' => $badge,
        ];
    }
}

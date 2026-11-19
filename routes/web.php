<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\Manage\GymController;
use App\Http\Controllers\Manage\GymQrController;
use App\Http\Controllers\Manage\MembershipApprovalController;
use App\Http\Controllers\Manage\MemberScanController;
use App\Http\Controllers\Manage\UserController;
use App\Http\Controllers\GymAdmin;
use App\Http\Controllers\Manage;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Member;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\SiteController;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;

/* ---------- Gym website (homepage) ---------- */
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::post('enquire', [SiteController::class, 'enquire'])->middleware('throttle:6,10')->name('site.enquire');
Route::get('site-media/{gym}/{file}', [MediaController::class, 'siteMedia'])->where('file', '[A-Za-z0-9_\-]+\.(webp|jpg|png|mp4)')->name('site.media');

/* ---------- FlexFit SaaS website ---------- */
Route::prefix('flexfit')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('flexfit');
    Route::get('features', [PageController::class, 'features'])->name('features');
    Route::get('how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
    Route::get('pricing', [PageController::class, 'pricing'])->name('pricing');
    Route::get('about', [PageController::class, 'about'])->name('about');
    Route::get('book-a-demo', [PageController::class, 'demo'])->name('demo');
    Route::post('book-a-demo', [PageController::class, 'storeDemo'])->middleware('throttle:5,10')->name('demo.store');
});
foreach (['features', 'how-it-works', 'pricing', 'about', 'book-a-demo'] as $old) {
    Route::permanentRedirect($old, "/flexfit/{$old}");
}

/* ---------- Public gym pages ---------- */
Route::get('gyms', [PageController::class, 'gyms'])->name('gyms');
Route::get('gyms/{gym:slug}', [PageController::class, 'gym'])->name('gyms.show');

/* Old single-gym website URLs → sections of the gym website. */
foreach (['services' => 'programs', 'membership-plans' => 'pricing', 'trainers' => 'trainers', 'facilities' => 'gallery', 'gallery' => 'gallery', 'contact' => 'contact'] as $old => $anchor) {
    Route::permanentRedirect($old, "/#{$anchor}");
}

/* ---------- Join (QR registration) ---------- */
Route::redirect('join', '/gyms')->name('join');
Route::middleware('guest')->group(function () {
    Route::get('join/{token}', [JoinController::class, 'show'])->name('join.show');
    Route::post('join/{token}/otp', [JoinController::class, 'sendOtp'])->middleware('throttle:5,10')->name('join.otp.send');
    Route::post('join/{token}/otp/verify', [JoinController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('join.otp.verify');
    Route::post('join/{token}', [JoinController::class, 'store'])->middleware('throttle:10,10')->name('join.store');
});

Route::post('webhooks/razorpay', RazorpayWebhookController::class)->name('webhooks.razorpay');

/* ---------- Guest auth ---------- */
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:10,10');
    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

/* Trainer avatars on the public Trainers page are viewable without login; the controller checks access. */
Route::get('media/avatar/{user}', [MediaController::class, 'avatar'])->name('media.avatar');
Route::get('media/gym-logo/{gym}', [MediaController::class, 'gymLogo'])->name('media.gym-logo');

/* ---------- Authenticated ---------- */
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('media/scans/{scan}/{view}', [MediaController::class, 'scan'])->where('view', 'front|side|back')->name('media.scan');

    Route::middleware('role:' . Roles::SUPER_ADMIN)->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'superAdmin'])->name('dashboard');

        Route::get('subscriptions', [SuperAdmin\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/create', [SuperAdmin\SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('subscriptions', [SuperAdmin\SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('subscriptions/{subscription}/edit', [SuperAdmin\SubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::put('subscriptions/{subscription}', [SuperAdmin\SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::get('plans/create', [SuperAdmin\SubscriptionController::class, 'createPlan'])->name('plans.create');
        Route::post('plans', [SuperAdmin\SubscriptionController::class, 'storePlan'])->name('plans.store');
        Route::get('plans/{plan}/edit', [SuperAdmin\SubscriptionController::class, 'editPlan'])->name('plans.edit');
        Route::put('plans/{plan}', [SuperAdmin\SubscriptionController::class, 'updatePlan'])->name('plans.update');

        Route::get('payments', [SuperAdmin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [SuperAdmin\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [SuperAdmin\PaymentController::class, 'store'])->name('payments.store');

        Route::get('members', [SuperAdmin\InsightsController::class, 'members'])->name('members');
        Route::get('revenue', [SuperAdmin\InsightsController::class, 'revenue'])->name('revenue');
        Route::get('ai-usage', [SuperAdmin\InsightsController::class, 'ai'])->name('ai');

        Route::get('support', [SuperAdmin\SupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [SuperAdmin\SupportController::class, 'show'])->name('support.show');
        Route::put('support/{ticket}', [SuperAdmin\SupportController::class, 'update'])->name('support.update');

        Route::get('demo-requests', [SuperAdmin\DemoRequestController::class, 'index'])->name('demos.index');
        Route::patch('demo-requests/{demo}', [SuperAdmin\DemoRequestController::class, 'update'])->name('demos.update');

        Route::get('settings', [SuperAdmin\SettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [SuperAdmin\SettingsController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:' . Roles::GYM_ADMIN)->prefix('gym-admin')->name('gym-admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'gymAdmin'])->name('dashboard');

        Route::get('enquiries', [GymAdmin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::post('enquiries', [GymAdmin\EnquiryController::class, 'store'])->name('enquiries.store');
        Route::get('enquiries/{enquiry}', [GymAdmin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::put('enquiries/{enquiry}', [GymAdmin\EnquiryController::class, 'update'])->name('enquiries.update');

        Route::get('fees', [GymAdmin\FeeController::class, 'index'])->name('fees.index');
        Route::get('fees/plans/create', [GymAdmin\FeeController::class, 'createPlan'])->name('fees.plans.create');
        Route::post('fees/plans', [GymAdmin\FeeController::class, 'storePlan'])->name('fees.plans.store');
        Route::get('fees/plans/{plan}/edit', [GymAdmin\FeeController::class, 'editPlan'])->name('fees.plans.edit');
        Route::put('fees/plans/{plan}', [GymAdmin\FeeController::class, 'updatePlan'])->name('fees.plans.update');
        Route::get('fees/members/{member}/renew', [GymAdmin\FeeController::class, 'renewForm'])->name('fees.renew');
        Route::post('fees/members/{member}/renew', [GymAdmin\FeeController::class, 'renew'])->name('fees.renew.store');

        Route::get('payments', [GymAdmin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [GymAdmin\PaymentController::class, 'export'])->name('payments.export');

        Route::get('attendance', [GymAdmin\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/{member}/check-in', [GymAdmin\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('attendance/visits/{attendance}/check-out', [GymAdmin\AttendanceController::class, 'checkOut'])->name('attendance.check-out');

        Route::get('trainers', [GymAdmin\TrainerController::class, 'index'])->name('trainers.index');
        Route::get('trainers/{trainer}/edit', [GymAdmin\TrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('trainers/{trainer}', [GymAdmin\TrainerController::class, 'update'])->name('trainers.update');

        Route::get('transformation', [GymAdmin\InsightController::class, 'transformation'])->name('transformation');
        Route::get('reports', [GymAdmin\InsightController::class, 'reports'])->name('reports');
        Route::get('reports/members.csv', [GymAdmin\InsightController::class, 'exportMembers'])->name('reports.members');

        Route::get('settings', [GymAdmin\SettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [GymAdmin\SettingsController::class, 'update'])->name('settings.update');
    });

    /* Workout & diet plan builder — gym owners and trainers */
    Route::middleware('role:' . Roles::GYM_ADMIN . '|' . Roles::TRAINER)->prefix('gym-admin/plans')->name('gym-admin.plans.')->group(function () {
        Route::get('/', [GymAdmin\PlanController::class, 'index'])->name('index');
        Route::get('{kind}/create', [GymAdmin\PlanController::class, 'create'])->where('kind', 'workout|diet')->name('create');
        Route::post('{kind}', [GymAdmin\PlanController::class, 'store'])->where('kind', 'workout|diet')->name('store');
        Route::get('{kind}/{plan}/edit', [GymAdmin\PlanController::class, 'edit'])->where('kind', 'workout|diet')->name('edit');
        Route::put('{kind}/{plan}', [GymAdmin\PlanController::class, 'update'])->where('kind', 'workout|diet')->name('update');
        Route::post('{kind}/{plan}/archive', [GymAdmin\PlanController::class, 'archive'])->where('kind', 'workout|diet')->name('archive');
    });

    /* Gym owners: front-desk registration and CSV import */
    Route::middleware(['role:' . Roles::GYM_ADMIN, 'permission:manage members'])->prefix('manage/members')->name('manage.members.')->group(function () {
        Route::get('add', [Manage\MemberController::class, 'create'])->name('create');
        Route::post('add', [Manage\MemberController::class, 'store'])->name('store');
        Route::get('import', [Manage\MemberController::class, 'importForm'])->name('import');
        Route::get('import/template', [Manage\MemberController::class, 'template'])->name('import.template');
        Route::post('import/preview', [Manage\MemberController::class, 'preview'])->middleware('throttle:20,10')->name('import.preview');
        Route::post('import', [Manage\MemberController::class, 'import'])->name('import.run');
    });

    /* Gym owners → FlexFit support */
    Route::middleware('role:' . Roles::GYM_ADMIN)->prefix('manage/support')->name('manage.support.')->group(function () {
        Route::get('/', [Manage\SupportController::class, 'index'])->name('index');
        Route::post('/', [Manage\SupportController::class, 'store'])->middleware('throttle:10,60')->name('store');
        Route::get('{ticket}', [Manage\SupportController::class, 'show'])->name('show');
    });

    Route::middleware('role:' . Roles::TRAINER)->prefix('trainer')->name('trainer.')->group(function () {
        Route::get('/', [DashboardController::class, 'trainer'])->name('dashboard');
    });

    /* Member portal */
    Route::middleware('role:' . Roles::MEMBER)->prefix('member')->name('member.')->group(function () {
        Route::get('/', Member\OverviewController::class)->name('dashboard');

        Route::get('profile', [Member\ProfileController::class, 'show'])->name('profile');
        Route::put('profile', [Member\ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/photo', [Member\ProfileController::class, 'updatePhoto'])->name('profile.photo');

        Route::get('membership', [Member\MembershipController::class, 'show'])->name('membership');
        Route::post('membership', [Member\MembershipController::class, 'store'])->name('membership.store');
        Route::post('membership/{membership}/checkout', [Member\MembershipController::class, 'checkout'])->middleware('throttle:10,1')->name('membership.checkout');
        Route::post('payments/verify', [Member\MembershipController::class, 'verify'])->name('payments.verify');

        Route::get('attendance', Member\AttendanceController::class)->name('attendance');
        Route::get('payments', [Member\AccountController::class, 'payments'])->name('payments');

        Route::get('scanner', [Member\BodyScanController::class, 'index'])->name('scanner');
        Route::post('scanner', [Member\BodyScanController::class, 'store'])->middleware('throttle:5,60')->name('scanner.store');
        Route::get('scanner/{scan}', [Member\BodyScanController::class, 'show'])->name('scanner.show');
        Route::get('scanner/{scan}/status', [Member\BodyScanController::class, 'status'])->name('scanner.status');
        Route::post('scanner/{scan}/retry', [Member\BodyScanController::class, 'retry'])->middleware('throttle:5,60')->name('scanner.retry');

        Route::get('transformation', [Member\TransformationController::class, 'show'])->name('transformation');
        Route::post('transformation/compare', [Member\TransformationController::class, 'generate'])->middleware('throttle:10,60')->name('transformation.compare');
        Route::get('transformation/comparisons/{comparison}/status', [Member\TransformationController::class, 'status'])->name('transformation.status');
        Route::get('progress', [Member\ProgressController::class, 'history'])->name('progress');
        Route::get('weight', [Member\ProgressController::class, 'weight'])->name('weight');
        Route::post('weight', [Member\ProgressController::class, 'storeWeight'])->name('weight.store');
        Route::delete('weight/{log}', [Member\ProgressController::class, 'destroyWeight'])->name('weight.destroy');
        Route::get('measurements', [Member\ProgressController::class, 'measurements'])->name('measurements');
        Route::post('measurements', [Member\ProgressController::class, 'storeMeasurement'])->name('measurements.store');
        Route::delete('measurements/{measurement}', [Member\ProgressController::class, 'destroyMeasurement'])->name('measurements.destroy');

        Route::get('workouts', [Member\AccountController::class, 'workouts'])->name('workouts');
        Route::get('diet', [Member\AccountController::class, 'diet'])->name('diet');
        Route::get('notifications', [Member\AccountController::class, 'notifications'])->name('notifications');
        Route::post('notifications/read-all', [Member\AccountController::class, 'readAllNotifications'])->name('notifications.read-all');
        Route::get('notifications/{id}', [Member\AccountController::class, 'readNotification'])->name('notifications.read');
        Route::get('settings', [Member\AccountController::class, 'settings'])->name('settings');
        Route::put('settings/email', [Member\AccountController::class, 'updateEmail'])->name('settings.email');
        Route::put('settings/password', [Member\AccountController::class, 'updatePassword'])->name('settings.password');
        Route::put('settings/preferences', [Member\AccountController::class, 'updatePreferences'])->name('settings.preferences');
    });

    /* Management (authorised per-action by policies / permissions) */
    Route::prefix('manage')->name('manage.')->group(function () {
        Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
        Route::resource('users', UserController::class)->except('show');

        Route::middleware('permission:manage members')->group(function () {
            Route::get('memberships', [MembershipApprovalController::class, 'index'])->name('memberships.index');
            Route::post('memberships/{membership}/approve', [MembershipApprovalController::class, 'approve'])->name('memberships.approve');
            Route::post('memberships/{membership}/reject', [MembershipApprovalController::class, 'reject'])->name('memberships.reject');

            // Scan corrections (audited)
            Route::get('members/{member}/scans', [MemberScanController::class, 'index'])->name('scans.index');
            Route::post('members/{member}/scans/override', [MemberScanController::class, 'override'])->name('scans.override');
            Route::post('scans/{scan}/void', [MemberScanController::class, 'void'])->name('scans.void');
            Route::post('scans/{scan}/restore', [MemberScanController::class, 'restore'])->name('scans.restore');
            Route::post('scans/{scan}/correct', [MemberScanController::class, 'correct'])->name('scans.correct');
            Route::post('scans/{scan}/retry', [MemberScanController::class, 'retry'])->name('scans.retry');
            Route::get('scans/{scan}/original/{view}', [MemberScanController::class, 'original'])->where('view', 'front|side|back')->name('scans.original');
        });

        Route::get('gyms/{gym}/qr', [GymQrController::class, 'show'])->name('gyms.qr');
        Route::get('gyms/{gym}/qr/download', [GymQrController::class, 'download'])->name('gyms.qr.download');
        Route::post('gyms/{gym}/qr/rotate', [GymQrController::class, 'rotate'])->name('gyms.qr.rotate');

        Route::middleware('permission:manage gyms')->group(function () {
            Route::resource('gyms', GymController::class)->except(['show', 'destroy']);
        });
    });
});

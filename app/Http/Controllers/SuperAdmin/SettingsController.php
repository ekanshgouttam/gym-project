<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('super-admin.settings', [
            'settings' => PlatformSetting::values(),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
            'gyms' => \App\Models\Gym::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'keySet' => filled(config('services.anthropic.api_key')),
            'model' => config('services.anthropic.model'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'default_plan' => ['required', Rule::exists('subscription_plans', 'slug')],
            'support_email' => ['nullable', 'email', 'max:255'],
            'ai_monthly_budget_usd' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'usd_to_inr' => ['required', 'numeric', 'min:1', 'max:1000'],
            'site_gym' => ['nullable', 'integer', Rule::exists('gyms', 'id')->where('is_active', true)],
        ]);
        $data['site_gym'] = (int) ($data['site_gym'] ?? 0);
        \Illuminate\Support\Facades\Cache::forget('site.stats.' . $data['site_gym']);

        $data['allow_gym_registration'] = $request->boolean('allow_gym_registration');
        $data['ai_scans_enabled'] = $request->boolean('ai_scans_enabled');
        $data['support_email'] = $data['support_email'] ?? '';

        PlatformSetting::put($data);

        return back()->with('status', 'Platform settings saved.');
    }
}

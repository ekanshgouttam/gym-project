<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Services\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gym profile (shown on the public page and join page) and reminder preferences. */
class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('gym-admin.settings', ['gym' => $request->user()->gym]);
    }

    public function update(Request $request, ImageStore $images)
    {
        $gym = $request->user()->gym;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('gyms', 'name')->ignore($gym->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:1000'],
            'reminder_days' => ['array'],
            'reminder_days.*' => [Rule::in([1, 3, 7, 15])],
        ]);

        $settings = array_merge($gym->settings ?? [], [
            'opening_hours' => $data['opening_hours'] ?? '',
            'about' => $data['about'] ?? '',
            'reminder_days' => array_values(array_map('intval', $data['reminder_days'] ?? [])),
            'expiry_reminders' => $request->boolean('expiry_reminders'),
            'payment_reminders' => $request->boolean('payment_reminders'),
            'notify_owner' => $request->boolean('notify_owner'),
        ]);

        $fill = [
            'name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null, 'city' => $data['city'] ?? null, 'settings' => $settings,
        ];
        if ($data['name'] !== $gym->name) {
            $fill['slug'] = Gym::uniqueSlug($data['name'], $gym->id); // public page URL follows the name; QR join link does not change
        }
        if ($request->hasFile('logo')) {
            $old = $gym->logo_path;
            $fill['logo_path'] = $images->storeLogo($request->file('logo'));
            $images->delete($old);
        }
        if ($request->boolean('remove_logo') && ! $request->hasFile('logo')) {
            $images->delete($gym->logo_path);
            $fill['logo_path'] = null;
        }

        $gym->update($fill);

        return back()->with('status', 'Gym settings saved.');
    }
}

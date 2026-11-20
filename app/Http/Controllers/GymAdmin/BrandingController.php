<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Services\ImageStore;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gym owner's branding: logo, primary and accent colours, and light/dark theme for the panels and mobile app. */
class BrandingController extends Controller
{
    public function edit(Request $request)
    {
        $gym = $request->user()->gym;

        return view('gym-admin.branding', ['gym' => $gym, 'brand' => Branding::for($gym)]);
    }

    public function update(Request $request, ImageStore $images)
    {
        $gym = $request->user()->gym;

        if ($request->boolean('reset')) {
            $gym->update(['settings' => array_merge($gym->settings ?? [], ['branding' => Branding::DEFAULTS])]);

            return back()->with('status', 'Branding reset to the default colours.');
        }

        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme' => ['required', Rule::in(array_keys(Branding::THEMES))],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'primary.regex' => 'Pick the primary colour with the colour picker (a value like #FFD400).',
            'accent.regex' => 'Pick the accent colour with the colour picker (a value like #DF6826).',
        ]);

        $fill = ['settings' => array_merge($gym->settings ?? [], [
            'branding' => ['primary' => strtoupper($data['primary']), 'accent' => strtoupper($data['accent']), 'theme' => $data['theme']],
        ])];

        if ($request->hasFile('logo')) {
            $old = $gym->logo_path;
            $fill['logo_path'] = $images->storeLogo($request->file('logo'));
            $images->delete($old);
        } elseif ($request->boolean('remove_logo')) {
            $images->delete($gym->logo_path);
            $fill['logo_path'] = null;
        }

        $gym->update($fill);

        return back()->with('status', 'Branding saved. Your team and members now see the new look.');
    }
}

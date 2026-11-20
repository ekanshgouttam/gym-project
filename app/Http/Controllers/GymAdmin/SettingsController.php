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
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:1000'],
            'reminder_days' => ['array'],
            'reminder_days.*' => [Rule::in([1, 3, 7, 15])],
            // Website media
            'hero_video' => ['nullable', 'file', 'mimetypes:video/mp4', 'max:25600'],
            'hero_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'gallery' => ['nullable', 'array', 'max:9'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_gallery' => ['nullable', 'array'],
        ], [
            'hero_video.mimetypes' => 'The hero video must be an MP4 file.',
            'hero_video.max' => 'The hero video must be smaller than 25 MB. A 10–20 second 720p clip works best.',
            'gallery.max' => 'You can upload up to 9 gallery photos at a time.',
            'gallery.*.mimes' => 'Gallery photos must be JPG, PNG or WebP.',
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
        $fill['settings']['site_media'] = $this->siteMedia($request, $gym, $images);
        $gym->update($fill);

        return back()->with('status', 'Gym settings saved.');
    }

    /** Hero video / image and gallery photos for the gym website. */
    private function siteMedia(Request $request, Gym $gym, ImageStore $images): array
    {
        $media = array_merge(['hero_video' => null, 'hero_image' => null, 'gallery' => []], $gym->setting('site_media') ?: []);

        if ($request->hasFile('hero_video')) {
            $images->deleteSiteFile($gym->id, $media['hero_video']);
            $name = \Illuminate\Support\Str::random(20) . '.mp4';
            $request->file('hero_video')->storeAs("site/{$gym->id}", $name, 'local');
            $media['hero_video'] = $name;
        } elseif ($request->boolean('remove_hero_video')) {
            $images->deleteSiteFile($gym->id, $media['hero_video']);
            $media['hero_video'] = null;
        }

        if ($request->hasFile('hero_image')) {
            $images->deleteSiteFile($gym->id, $media['hero_image']);
            $media['hero_image'] = $images->storeSiteImage($request->file('hero_image'), $gym->id, 'hero_image');
        } elseif ($request->boolean('remove_hero_image')) {
            $images->deleteSiteFile($gym->id, $media['hero_image']);
            $media['hero_image'] = null;
        }

        $remove = array_map('basename', (array) $request->input('remove_gallery', []));
        foreach ($remove as $file) {
            $images->deleteSiteFile($gym->id, $file);
        }
        $media['gallery'] = array_values(array_diff($media['gallery'], $remove));
        foreach ((array) $request->file('gallery', []) as $photo) {
            if (count($media['gallery']) >= 9) {
                break;
            }
            $media['gallery'][] = $images->storeSiteImage($photo, $gym->id, 'gallery');
        }

        return $media;
    }
}

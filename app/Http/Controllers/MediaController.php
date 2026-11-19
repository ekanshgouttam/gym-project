<?php

namespace App\Http\Controllers;

use App\Models\BodyScan;
use App\Models\Gym;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Streams private images after an access check. Nothing under storage/app is web-served directly. */
class MediaController extends Controller
{
    public function avatar(Request $request, User $user)
    {
        $publicTrainer = $user->hasRole(Roles::TRAINER) && optional($user->trainerProfile)->is_public;

        if (! $publicTrainer) {
            $viewer = $request->user();
            abort_unless($viewer && $this->canSee($viewer, $user), 404);
        }

        return $this->stream($user->avatar_path, $publicTrainer ? 'public, max-age=86400' : 'private, max-age=3600');
    }

    /** Gym logos are public: they appear on the gym's QR join page and public page. */
    public function gymLogo(Gym $gym)
    {
        return $this->stream($gym->logo_path, 'public, max-age=86400');
    }

    /** Website photos and video the gym owner uploaded (public by design). */
    public function siteMedia(Gym $gym, string $file)
    {
        $path = "site/{$gym->id}/" . basename($file);
        abort_unless(Storage::disk('local')->exists($path), 404);
        $types = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'mp4' => 'video/mp4'];

        // Range-capable response so browsers can stream video.
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $types[pathinfo($path, PATHINFO_EXTENSION)] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function scan(Request $request, BodyScan $scan, string $view)
    {
        abort_unless($this->canSee($request->user(), $scan->user), 404);

        return $this->stream($scan->photoPath($view), 'private, max-age=3600');
    }

    /** Self, Super Admin, or Gym Admin / Trainer of the same gym. */
    private function canSee(User $viewer, User $owner): bool
    {
        if ($viewer->id === $owner->id || $viewer->isSuperAdmin()) {
            return true;
        }

        return $viewer->gym_id !== null
            && $viewer->gym_id === $owner->gym_id
            && $viewer->hasAnyRole([Roles::GYM_ADMIN, Roles::TRAINER]);
    }

    private function stream(?string $path, string $cache)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => substr($path, -4) === '.png' ? 'image/png' : 'image/jpeg',
            'Cache-Control' => $cache,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

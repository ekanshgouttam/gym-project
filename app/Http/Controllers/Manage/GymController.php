<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Gym branch management — Super Admin only (route middleware: permission:manage gyms). */
class GymController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');
        $gyms = Gym::with('owner:id,name,email', 'subscription.plan')
            ->withCount('users')
            ->when($filter === 'active', function ($q) { $q->where('is_active', true); })
            ->when($filter === 'inactive', function ($q) { $q->where('is_active', false); })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($w) use ($term) { $w->where('name', 'like', $term)->orWhere('city', 'like', $term)->orWhere('email', 'like', $term); });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('manage.gyms.index', [
            'gyms' => $gyms,
            'filter' => $filter,
            'activeMembers' => \App\Support\PlatformStats::activeMembersByGym(),
            'counts' => ['all' => Gym::count(), 'active' => Gym::where('is_active', true)->count(), 'inactive' => Gym::where('is_active', false)->count()],
        ]);
    }

    public function create()
    {
        return view('manage.gyms.form', ['gym' => new Gym(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $gym = Gym::create($this->validated($request));

        return redirect()->route('manage.gyms.index')->with('status', "{$gym->name} was created.");
    }

    public function edit(Gym $gym)
    {
        return view('manage.gyms.form', compact('gym'));
    }

    public function update(Request $request, Gym $gym)
    {
        $gym->update($this->validated($request, $gym));

        return redirect()->route('manage.gyms.index')->with('status', "{$gym->name} was updated.");
    }

    private function validated(Request $request, ?Gym $gym = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('gyms', 'name')->ignore(optional($gym)->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);
        $data['slug'] = Gym::uniqueSlug($data['name'], optional($gym)->id);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

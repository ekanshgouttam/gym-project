<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Book a Demo" leads from the marketing site. */
class DemoRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        return view('super-admin.demos', [
            'demos' => DemoRequest::when($status && isset(DemoRequest::STATUSES[$status]), function ($q) use ($status) { $q->where('status', $status); })
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => DemoRequest::groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, DemoRequest $demo)
    {
        $demo->update($request->validate(['status' => ['required', Rule::in(array_keys(DemoRequest::STATUSES))]]));

        return back()->with('status', "Demo request from {$demo->name} marked " . DemoRequest::STATUSES[$demo->status] . '.');
    }
}

<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Support requests from gym owners, answered by the Super Admin. */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'unresolved');

        $tickets = SupportRequest::with('gym:id,name', 'user:id,name,email')
            ->when($status === 'unresolved', function ($q) { $q->unresolved(); })
            ->when(isset(SupportRequest::STATUSES[$status]), function ($q) use ($status) { $q->where('status', $status); })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.support.index', [
            'tickets' => $tickets,
            'status' => $status,
            'counts' => SupportRequest::groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status'),
        ]);
    }

    public function show(SupportRequest $ticket)
    {
        return view('super-admin.support.show', ['ticket' => $ticket->load('gym', 'user')]);
    }

    public function update(Request $request, SupportRequest $ticket)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SupportRequest::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(SupportRequest::PRIORITIES))],
            'admin_reply' => ['nullable', 'string', 'max:5000'],
        ]);

        if (($data['admin_reply'] ?? null) !== $ticket->admin_reply && filled($data['admin_reply'] ?? null)) {
            $data['replied_at'] = now();
        }
        $data['resolved_at'] = in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?: now()) : null;

        $ticket->update($data);

        return redirect()->route('super-admin.support.show', $ticket)->with('status', 'Support request updated.');
    }
}

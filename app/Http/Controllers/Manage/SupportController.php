<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gym owners raise and follow their support requests to FlexFit. */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        return view('manage.support.index', [
            'tickets' => SupportRequest::where('gym_id', $request->user()->gym_id)->latest()->paginate(15),
            'supportEmail' => PlatformSetting::get('support_email'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(SupportRequest::CATEGORIES))],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])], // "urgent" is set by FlexFit staff
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportRequest::create($data + ['gym_id' => $request->user()->gym_id, 'user_id' => $request->user()->id]);

        return redirect()->route('manage.support.show', $ticket)->with('status', 'Thanks! The FlexFit team will reply here.');
    }

    public function show(Request $request, SupportRequest $ticket)
    {
        abort_unless($ticket->gym_id === $request->user()->gym_id, 404);

        return view('manage.support.show', ['ticket' => $ticket]);
    }
}

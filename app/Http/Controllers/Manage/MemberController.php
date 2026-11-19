<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\MemberProfile;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MemberRegistrationService;
use App\Services\OtpService;
use App\Support\MemberCsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Gym owner: register members at the front desk, or bring many in from a CSV file. */
class MemberController extends Controller
{
    private const IMPORT_SESSION_KEY = 'member_import';

    /* ---------- Manual registration ---------- */

    public function create(Request $request)
    {
        $enquiry = $request->filled('enquiry')
            ? Enquiry::where('gym_id', $request->user()->gym_id)->open()->find($request->query('enquiry'))
            : null;

        return view('manage.members.create', [
            'plans' => MembershipPlan::availableAt($request->user()->gym_id)->get(),
            'goals' => MemberProfile::GOALS,
            'enquiry' => $enquiry,
            'trainers' => User::role(\App\Support\Roles::TRAINER)->where('gym_id', $request->user()->gym_id)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, MemberRegistrationService $registrar)
    {
        $gym = $request->user()->gym;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:-10 years', 'after:-100 years'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'height_cm' => ['nullable', 'numeric', 'between:100,250'],
            'weight_kg' => ['nullable', 'numeric', 'between:25,300'],
            'fitness_goal' => ['nullable', Rule::in(array_keys(MemberProfile::GOALS))],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'membership_plan_id' => ['nullable', Rule::in(MembershipPlan::availableAt($gym->id)->pluck('id')->all())],
            'payment' => ['required_with:membership_plan_id', 'nullable', Rule::in(['paid', 'pending'])],
            'payment_method' => ['nullable', Rule::in(array_keys(\App\Models\Payment::DESK_METHODS))],
            'trainer_id' => ['nullable', Rule::in(User::role(\App\Support\Roles::TRAINER)->where('gym_id', $gym->id)->pluck('id')->all())],
            'enquiry_id' => ['nullable', 'integer'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'membership_plan_id.in' => 'Please choose one of the plans offered at your gym.',
        ]);

        $data['phone'] = $this->phone($data['phone'], 'phone');
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered.']);
        }
        if (! empty($data['emergency_contact_phone'])) {
            $data['emergency_contact_phone'] = $this->phone($data['emergency_contact_phone'], 'emergency_contact_phone');
        }

        $plan = ! empty($data['membership_plan_id']) ? MembershipPlan::find($data['membership_plan_id']) : null;
        $member = $registrar->register($gym, $data, 'manual', $request->user(), $plan, ($data['payment'] ?? null) === 'paid');

        // Converting an enquiry: link it to the new member for conversion tracking.
        if (! empty($data['enquiry_id'])) {
            $enquiry = Enquiry::where('gym_id', $gym->id)->open()->find($data['enquiry_id']);
            if ($enquiry) {
                $enquiry->update(['status' => 'converted', 'converted_user_id' => $member->id, 'converted_at' => now(), 'next_follow_up_on' => null]);
                $enquiry->log($request->user(), "Joined as member {$member->memberProfile->member_code}");
            }
        }

        $msg = "{$member->name} is registered. Member ID: {$member->memberProfile->member_code}.";
        if (empty($data['password'])) {
            $msg .= ' They can set a password with "Forgot password" on the login page.';
        }

        return redirect()->route('manage.users.index', ['role' => 'member'])->with('status', $msg);
    }

    /* ---------- CSV import ---------- */

    public function importForm(Request $request)
    {
        return view('manage.members.import', [
            'plans' => MembershipPlan::availableAt($request->user()->gym_id)->get(),
            'preview' => null,
        ]);
    }

    public function template()
    {
        $rows = [
            MemberCsvImport::COLUMNS,
            ['Priya Sharma', 'priya@example.com', '9820012345', '1995-04-12', 'female', 'Monthly', 'yes'],
            ['Rahul Verma', 'rahul@example.com', '98200 54321', '22/08/1990', 'male', '', ''],
        ];
        $csv = implode("\r\n", array_map(function ($r) {
            return implode(',', array_map(function ($c) { return '"' . str_replace('"', '""', $c) . '"'; }, $r));
        }, $rows)) . "\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="flexfit-members-template.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], [
            'file.mimes' => 'Please upload a .csv file (in Excel: File → Save As → CSV).',
        ]);

        $gym = $request->user()->gym;
        $result = MemberCsvImport::parse($request->file('file')->getRealPath(), $gym);
        if ($result['error']) {
            throw ValidationException::withMessages(['file' => $result['error']]);
        }

        // Clear abandoned previews (never confirmed) older than a day.
        foreach (Storage::disk('local')->files('imports') as $file) {
            if (Storage::disk('local')->lastModified($file) < now()->subDay()->timestamp) {
                Storage::disk('local')->delete($file);
            }
        }

        // Keep the checked rows server-side until the owner confirms; nothing is saved yet.
        $token = (string) Str::uuid();
        Storage::disk('local')->put("imports/{$token}.json", json_encode(['gym_id' => $gym->id, 'rows' => $result['rows']]));
        $old = $request->session()->get(self::IMPORT_SESSION_KEY);
        if ($old) {
            Storage::disk('local')->delete("imports/{$old}.json");
        }
        $request->session()->put(self::IMPORT_SESSION_KEY, $token);

        return view('manage.members.import', [
            'plans' => MembershipPlan::availableAt($gym->id)->get(),
            'preview' => collect($result['rows']),
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function import(Request $request, MemberRegistrationService $registrar)
    {
        $token = $request->session()->pull(self::IMPORT_SESSION_KEY);
        $path = "imports/{$token}.json";
        if (! $token || ! Storage::disk('local')->exists($path)) {
            return redirect()->route('manage.members.import')->withErrors(['file' => 'That preview expired. Please upload the file again.']);
        }

        $stored = json_decode(Storage::disk('local')->get($path), true);
        Storage::disk('local')->delete($path);
        $gym = $request->user()->gym;
        abort_unless((int) $stored['gym_id'] === (int) $gym->id, 403);

        $notify = $request->boolean('notify');
        $plans = MembershipPlan::availableAt($gym->id)->get()->keyBy('id');
        $created = 0;
        $skipped = [];

        foreach ($stored['rows'] as $row) {
            if ($row['errors']) {
                continue;
            }
            $data = $row['data'];
            // Re-check uniqueness: someone may have registered since the preview.
            if (User::where('email', $data['email'])->orWhere('phone', $data['phone'])->exists()) {
                $skipped[] = $row['line'];
                continue;
            }
            $plan = isset($data['plan_id']) ? $plans->get($data['plan_id']) : null;
            $registrar->register($gym, $data, 'import', $request->user(), $plan, $plan && $data['paid'], $notify);
            $created++;
        }

        $invalid = collect($stored['rows'])->filter(function ($r) { return $r['errors']; })->count();
        $msg = "Imported {$created} " . Str::plural('member', $created) . '.';
        if ($invalid) {
            $msg .= " {$invalid} " . Str::plural('row', $invalid) . ' with problems were skipped.';
        }
        if ($skipped) {
            $msg .= ' Already registered meanwhile: line ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('manage.users.index', ['role' => 'member'])->with('status', $msg);
    }

    private function phone(string $raw, string $field): string
    {
        $phone = OtpService::normalizePhone($raw);
        if (! $phone) {
            throw ValidationException::withMessages([$field => 'Enter a valid 10-digit Indian mobile number.']);
        }

        return $phone;
    }
}

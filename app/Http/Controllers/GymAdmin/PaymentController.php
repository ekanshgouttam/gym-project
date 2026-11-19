<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Every member payment at this gym, online and at the desk, with CSV export. */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->query($request);

        return view('gym-admin.payments', [
            'payments' => (clone $query)->with('user:id,name', 'membership.plan:id,name', 'recorder:id,name')->latest('paid_at')->latest('id')->paginate(25)->withQueryString(),
            'total' => (int) round((clone $query)->sum('amount_paise') / 100),
            'count' => (clone $query)->count(),
            'byMethod' => (clone $query)->groupBy('gateway')->selectRaw('gateway, SUM(amount_paise) as paise, COUNT(*) as n')->orderByDesc('paise')->get(),
            'month' => $request->query('month', now()->format('Y-m')),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $payments = $this->query($request)->with('user:id,name,phone', 'user.memberProfile:id,user_id,member_code', 'membership.plan:id,name', 'recorder:id,name')->orderBy('paid_at')->get();
        $name = 'payments-' . $request->query('month', now()->format('Y-m')) . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Member', 'Member ID', 'Mobile', 'Plan', 'Amount (INR)', 'Method', 'Reference', 'Recorded by', 'Note']);
            foreach ($payments as $p) {
                fputcsv($out, [
                    optional($p->paid_at)->format('Y-m-d H:i'), optional($p->user)->name, optional(optional($p->user)->memberProfile)->member_code,
                    optional($p->user)->phone, optional(optional($p->membership)->plan)->name, number_format($p->amount(), 2, '.', ''),
                    $p->methodLabel(), $p->gateway_payment_id, optional($p->recorder)->name ?: ($p->gateway === 'razorpay' ? 'Online' : ''), $p->note,
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function query(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $month = $request->query('month', now()->format('Y-m'));
        $start = preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::createFromFormat('!Y-m', $month) : now()->startOfMonth();

        return Payment::where('status', 'paid')
            ->whereHas('membership', function ($q) use ($gymId) { $q->where('gym_id', $gymId); })
            ->whereBetween('paid_at', [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()])
            ->when($request->filled('method'), function ($q) use ($request) { $q->where('gateway', $request->query('method')); });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // --- Validate incoming filters (range + legacy single date) ---
        $filters = $request->validate([
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'date'        => ['nullable', 'date'], // legacy (if provided, we map to from/to)
            'service'     => ['nullable','in:Massor,Hudterapeut'],
            'status'      => ['nullable','in:open,booked,completed,cancel'],
            'customer_id' => ['nullable','integer','exists:users,id'],
            'employee_id' => ['nullable','integer','exists:users,id'],
        ]);

        // --- Normalize legacy single date -> range ---
        if (!empty($filters['date']) && empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = $filters['date'];
            $filters['date_to']   = $filters['date'];
        }
        unset($filters['date']); // keep logic clean below

        // --- Normalize range order if swapped ---
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            if ($filters['date_from'] > $filters['date_to']) {
                [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
            }
        }

        // --- Enforce role scoping (server always authoritative) ---
        if ($user->user_type === 'customer') {
            $filters['customer_id'] = $user->id;
            unset($filters['employee_id']);
        } elseif ($user->user_type === 'employee') {
            $filters['employee_id'] = $user->id;
            unset($filters['customer_id']);
        }

        // --- Base query ---
        $query = Shift::with(['customer:id,name,email', 'employee:id,name,email'])
            ->latest('date');

        // --- Apply filters ---
        $query
            // Date range (inclusive)
            ->when(!empty($filters['date_from']) && !empty($filters['date_to']), function ($q) use ($filters) {
                $from = Carbon::parse($filters['date_from'])->startOfDay();
                $to   = Carbon::parse($filters['date_to'])->endOfDay();
                $q->whereBetween('date', [$from, $to]);
            })
            // Only from
            ->when(!empty($filters['date_from']) && empty($filters['date_to']), function ($q) use ($filters) {
                $from = Carbon::parse($filters['date_from'])->startOfDay();
                $q->where('date', '>=', $from);
            })
            // Only to
            ->when(empty($filters['date_from']) && !empty($filters['date_to']), function ($q) use ($filters) {
                $to = Carbon::parse($filters['date_to'])->endOfDay();
                $q->where('date', '<=', $to);
            })
            ->when(!empty($filters['service']),     fn($q) => $q->where('service', $filters['service']))
            ->when(!empty($filters['status']),      fn($q) => $q->where('status',  $filters['status']))
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']));

        $rows = $query->get();

        // --- Map for frontend ---
        $shifts = $rows->map(function ($s) {
            return [
                'id'         => $s->id,
                'date'       => optional($s->date)->toDateString(),
                'start_time' => $s->start_time,
                'end_time'   => $s->end_time,
                'service'    => $s->service,
                'status'     => $s->status,
                'customer'   => $s->customer
                    ? ['id' => $s->customer_id, 'name' => $s->customer->name, 'email' => $s->customer->email]
                    : null,
                'employee'   => $s->employee
                    ? ['id' => $s->employee_id, 'name' => $s->employee->name, 'email' => $s->employee->email]
                    : null,
            ];
        });

        // --- Dropdown sources (role-aware) ---
        $customers = in_array($user->user_type, ['admin','employee'])
            ? User::select('id','name','email')->where('user_type','customer')->orderBy('name')->get()
            : collect();

        $employees = in_array($user->user_type, ['admin','customer'])
            ? User::select('id','name','email')->where('user_type','employee')->orderBy('name')->get()
            : collect();

        // --- Return (prefill new range fields) ---
        return Inertia::render('shifts/Index', [
            'shifts'    => $shifts,
            'customers' => $customers,
            'employees' => $employees,
            'filters'   => [
                'date_from'   => $filters['date_from'] ?? '',
                'date_to'     => $filters['date_to']   ?? '',
                'service'     => $filters['service']   ?? '',
                'status'      => $filters['status']    ?? '',
                'customer_id' => $filters['customer_id'] ?? '',
                'employee_id' => $filters['employee_id'] ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'         => ['required', 'date'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'service'      => ['required', 'string', 'max:255'],
            'customer_id'  => ['required', 'exists:users,id'],
            'employee_id'  => ['required', 'exists:users,id'],
            'status'       => ['nullable', 'in:open,booked,completed,canceled'],
        ]);

        Shift::create($data);

        return back()->with('success', 'Shift created.');
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'date'         => ['required', 'date'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'service'      => ['required', 'string', 'max:255'],
            'customer_id'  => ['required', 'exists:users,id'],
            'employee_id'  => ['required', 'exists:users,id'],
            'status'       => ['nullable', 'in:open,booked,completed,canceled'],
        ]);

        $shift->update($data);

        return back()->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return back()->with('success', 'Shift deleted.');
    }
}

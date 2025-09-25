<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // --- Validate incoming filters (fixed services + known statuses) ---
        $filters = $request->validate([
            'date'        => ['nullable','date'],
            'service'     => ['nullable','in:Massor,Hudterapeut'],
            'status'      => ['nullable','in:open,booked,completed,cancel'],
            'customer_id' => ['nullable','integer','exists:users,id'],
            'employee_id' => ['nullable','integer','exists:users,id'],
        ]);

        // --- Enforce role scoping (ignore conflicting filters from client) ---
        if ($user->user_type === 'customer') {
            $filters['customer_id'] = $user->id;
            unset($filters['employee_id']); // customer cannot scope by another employee in backend
        } elseif ($user->user_type === 'employee') {
            $filters['employee_id'] = $user->id;
            unset($filters['customer_id']); // employee cannot scope by another customer in backend
        }

        // --- Base query ---
        $query = Shift::with(['customer:id,name,email', 'employee:id,name,email'])
            ->latest('date');

        // --- Apply filters if present ---
        $query
            ->when(!empty($filters['date']),        fn($q) => $q->whereDate('date', $filters['date']))
            ->when(!empty($filters['service']),     fn($q) => $q->where('service', $filters['service']))
            ->when(!empty($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(!empty($filters['employee_id']), fn($q) => $q->where('employee_id', $filters['employee_id']));

        $rows = $query->get();

        // --- Map for frontend (customer/employee as null when missing) ---
        $shifts = $rows->map(function ($s) {
            return [
                'id'         => $s->id,
                'date'       => optional($s->date)->toDateString(),
                'start_time' => $s->start_time, // "HH:mm" (ensure accessor/DB consistency)
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

        // --- Dropdown sources (limit by role as needed) ---
        // Admin sees both lists; customers only need employees; employees only need customers.
        $customers = User::select('id','name','email')
            ->when($user->user_type !== 'employee' && $user->user_type !== 'admin', fn($q) => $q->whereRaw('1=0')) // hide for customer
            ->when($user->user_type !== 'customer' && $user->user_type !== 'admin', fn($q) => $q)                 // show for employee/admin
            ->where('user_type','customer')
            ->orderBy('name')
            ->get();

        $employees = User::select('id','name','email')
            ->when($user->user_type !== 'customer' && $user->user_type !== 'admin', fn($q) => $q->whereRaw('1=0')) // hide for employee
            ->where('user_type','employee')
            ->orderBy('name')
            ->get();

        // Return applied filters so UI can prefill the modal
        return Inertia::render('shifts/Index', [
            'shifts'    => $shifts,
            'customers' => $customers,
            'employees' => $employees,
            'filters'   => [
                'date'        => $filters['date']        ?? '',
                'service'     => $filters['service']     ?? '',
                'status'      => $filters['status']      ?? '',
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

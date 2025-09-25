<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index()
    {
        // Load shifts with related names
        $shifts = Shift::with(['customer:id,name,email', 'employee:id,name,email'])
            ->latest('date')
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'date'         => $s->date?->toDateString(),
                    'start_time'   => $s->start_time,
                    'end_time'     => $s->end_time,
                    'service'      => $s->service,
                    'status'       => $s->status,
                    'customer'     => [
                        'id'    => $s->customer_id,
                        'name'  => $s->customer?->name,
                        'email' => $s->customer?->email,
                    ],
                    'employee'     => [
                        'id'    => $s->employee_id,
                        'name'  => $s->employee?->name,
                        'email' => $s->employee?->email,
                    ],
                ];
            });

        // Provide dropdown sources (filter however you mark roles)
        $customers = User::select('id', 'name', 'email')->where('user_type','customer')->orderBy('name')->get();
        $employees = User::select('id', 'name', 'email')->where('user_type','employee')->orderBy('name')->get();

        return Inertia::render('shifts/Index', [
            'shifts'    => $shifts,
            'customers' => $customers,
            'employees' => $employees,
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $employees = User::where('user_type', 'employee')->with('customers')->get();
        $customers = User::query()
            ->where('user_type', 'customer')
            ->select('id','name','email')
            ->orderBy('name')
            ->get();
        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'customers' => $customers,
        ]);
    }


    public function store(Request $request): RedirectResponse
    {
        if (Auth::user()->user_type !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'nullable|numeric|min:0',
            'customer_ids' => ['nullable','array'],
            'customer_ids.*' => ['integer',
                \Illuminate\Validation\Rule::exists('users','id')->where('user_type','customer')
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $employee  = User::create(array_merge($validated, [
            'user_type' => 'employee',
        ]));
        $employee->customers()->sync($validated['customer_ids'] ?? []);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }


    public function update(Request $request, User $user): RedirectResponse
    {
        if (Auth::user()->user_type !== 'admin' || $user->user_type !== 'employee') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'nullable|numeric|min:0',
            'customer_ids' => ['nullable','array'],
            'customer_ids.*' => ['integer',
                \Illuminate\Validation\Rule::exists('users','id')->where('user_type','customer')
            ],
//            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = $validated;
        if (isset($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }
        $user->update($updateData);
        $user->customers()->sync($validated['customer_ids'] ?? []);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::user()->user_type !== 'admin' || $user->user_type !== 'employee') {
            abort(403);
        }

        $user->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}

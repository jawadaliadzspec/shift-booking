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

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = User::where('user_type', 'customer')->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create(): Response
    {
        if (Auth::user()->user_type !== 'admin') {
            abort(403);
        }

        return Inertia::render('Customers/Create');
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
//            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create(array_merge($validated, [
            'user_type' => 'customer',
            'password' => bcrypt('password'),
        ]));

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(User $user): Response
    {
        if (Auth::user()->user_type !== 'admin' || $user->user_type !== 'customer') {
            abort(403);
        }

        return Inertia::render('Customers/Edit', [
            'customer' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (Auth::user()->user_type !== 'admin' || $user->user_type !== 'customer') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'nullable|numeric|min:0',
//            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update(array_merge($validated, [
            'password' => isset($validated['password']) ? bcrypt($validated['password']) : $user->password,
        ]));

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::user()->user_type !== 'admin') {
            abort(403);
        }

        $user->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}

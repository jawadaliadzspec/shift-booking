<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return redirect('/shifts');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Employees
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/employees/{user}', [\App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{user}', [\App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Customers
    Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{user}/edit', [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{user}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{user}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
    // Shifts
    Route::get('/shifts', [\App\Http\Controllers\ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/create', [\App\Http\Controllers\ShiftController::class, 'create'])->name('shifts.create');
    Route::post('/shifts', [\App\Http\Controllers\ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/{shift}/edit', [\App\Http\Controllers\ShiftController::class, 'edit'])->name('shifts.edit');
    Route::put('/shifts/{shift}', [\App\Http\Controllers\ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('/shifts/{shift}', [\App\Http\Controllers\ShiftController::class, 'destroy'])->name('shifts.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

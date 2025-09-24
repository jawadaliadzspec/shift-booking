<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Employees
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [\App\Http\Controllers\EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{user}/edit', [\App\Http\Controllers\EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{user}', [\App\Http\Controllers\EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{user}', [\App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Customers
    Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [\App\Http\Controllers\CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{user}/edit', [\App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{user}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{user}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

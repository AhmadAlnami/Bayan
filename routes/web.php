<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('transactions/expenses', [TransactionController::class, 'index'])
        ->defaults('type', 'expense')
        ->name('transactions.expenses');

    Route::get('transactions/income', [TransactionController::class, 'index'])
        ->defaults('type', 'income')
        ->name('transactions.income');

    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('transactions/quick', [TransactionController::class, 'quickStore'])->name('transactions.quick-store');
    Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
});

require __DIR__.'/settings.php';

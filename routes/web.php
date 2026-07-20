<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('insights', [InsightsController::class, 'index'])->name('insights');

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

    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets');
    Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    Route::get('reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('reports/print', [ReportsController::class, 'print'])->name('reports.print');

    Route::get('savings', [SavingsController::class, 'index'])->name('savings');
    Route::post('savings', [SavingsController::class, 'store'])->name('savings.store');
    Route::patch('savings/{savingsGoal}', [SavingsController::class, 'update'])->name('savings.update');
    Route::delete('savings/{savingsGoal}', [SavingsController::class, 'destroy'])->name('savings.destroy');
    Route::post('savings/{savingsGoal}/deposit', [SavingsController::class, 'deposit'])->name('savings.deposit');
});

require __DIR__.'/settings.php';

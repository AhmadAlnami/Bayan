<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request, string $type = 'expense'): Response
    {
        $transactions = $request->user()
            ->transactions()
            ->with('category')
            ->where('type', $type)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'category' => $t->category ? [
                    'id' => $t->category->id,
                    'name' => $t->category->name,
                    'name_en' => $t->category->name_en,
                    'color' => $t->category->color,
                ] : null,
            ]);

        $categories = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
        })
            ->where('type', $type)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'name_en' => $c->name_en,
                'color' => $c->color,
            ]);

        return Inertia::render('Transactions', [
            'type' => $type,
            'transactions' => $transactions->values(),
            'categories' => $categories->values(),
        ]);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $request->validate([
            'text' => 'required|string|min:2',
            'type' => 'required|in:expense,income',
        ]);

        $text = $request->text;
        $amount = 0;
        $description = '';

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(.+)$/u', $text, $matches)) {
            $amount = (float) $matches[1];
            $description = trim($matches[2]);
        } else {
            $description = $text;
        }

        if ($amount <= 0) {
            return Redirect::back()->with('toast', ['type' => 'error', 'message' => 'الرجاء إدخال المبلغ أولاً، مثال: 45 ريال قهوة']);
        }

        $category = Category::where('type', $request->type)->whereNull('user_id')->where('name', 'أخرى')->first();

        $request->user()->transactions()->create([
            'amount' => $amount, 'description' => $description ?: 'معاملة سريعة',
            'transaction_date' => Carbon::today(), 'category_id' => $category?->id, 'type' => $request->type,
        ]);

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => 'تمت الإضافة']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01', 'description' => 'required|string|max:255',
            'transaction_date' => 'required|date', 'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:expense,income',
        ]);
        $request->user()->transactions()->create($validated);

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => 'تمت الإضافة']);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01', 'description' => 'required|string|max:255',
            'transaction_date' => 'required|date', 'category_id' => 'nullable|exists:categories,id',
        ]);
        $transaction->update($validated);

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => 'تم التعديل']);
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }
        $transaction->delete();

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => 'تم الحذف']);
    }
}

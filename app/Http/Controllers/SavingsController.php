<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function index(Request $request): Response
    {
        $goals = $request->user()->savingsGoals()
            ->with('deposits')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'name_en' => $goal->name_en,
                'target_amount' => (float) $goal->target_amount,
                'current_amount' => (float) $goal->current_amount,
                'category' => $goal->category,
                'category_en' => $goal->category_en,
                'deadline' => $goal->deadline?->format('Y-m-d'),
                'auto_save_amount' => $goal->auto_save_amount ? (float) $goal->auto_save_amount : null,
                'auto_save_day' => $goal->auto_save_day,
                'is_active' => $goal->is_active,
                'progress' => $goal->progress,
                'remaining' => $goal->remaining,
                'recent_deposits' => $goal->deposits->sortByDesc('created_at')->take(5)->map(fn ($d) => [
                    'id' => $d->id,
                    'amount' => (float) $d->amount,
                    'source' => $d->source,
                    'note' => $d->note,
                    'created_at' => $d->created_at->format('Y-m-d'),
                ])->values(),
            ]);

        return Inertia::render('Savings', [
            'goals' => $goals->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'category' => 'nullable|string|max:50',
            'deadline' => 'nullable|date',
            'auto_save_amount' => 'nullable|numeric|min:0',
            'auto_save_day' => 'nullable|integer|min:1|max:28',
        ]);

        $request->user()->savingsGoals()->create([
            'name' => $validated['name'],
            'name_en' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'category' => $validated['category'] ?? 'أخرى',
            'category_en' => $validated['category'] ?? ($request->user()->locale === 'en' ? 'Other' : 'أخرى'),
            'deadline' => $validated['deadline'] ?? null,
            'auto_save_amount' => $validated['auto_save_amount'] ?? null,
            'auto_save_day' => $validated['auto_save_day'] ?? null,
        ]);

        return Redirect::route('savings')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Savings goal created!' : 'تم إنشاء هدف الادخار!',
        ]);
    }

    public function update(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'deadline' => 'nullable|date',
            'auto_save_amount' => 'nullable|numeric|min:0',
            'auto_save_day' => 'nullable|integer|min:1|max:28',
        ]);

        $savingsGoal->update([
            'name' => $validated['name'],
            'name_en' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'deadline' => $validated['deadline'] ?? $savingsGoal->deadline,
            'auto_save_amount' => $validated['auto_save_amount'] ?? $savingsGoal->auto_save_amount,
            'auto_save_day' => $validated['auto_save_day'] ?? $savingsGoal->auto_save_day,
        ]);

        return Redirect::route('savings')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Savings goal updated!' : 'تم تحديث هدف الادخار!',
        ]);
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            abort(403);
        }

        $savingsGoal->delete();

        return Redirect::route('savings')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Savings goal deleted!' : 'تم حذف هدف الادخار!',
        ]);
    }

    public function deposit(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        if ($savingsGoal->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        $savingsGoal->deposits()->create([
            'user_id' => $request->user()->id,
            'amount' => $validated['amount'],
            'source' => 'manual',
            'note' => $validated['note'] ?? null,
        ]);

        $savingsGoal->increment('current_amount', $validated['amount']);

        return Redirect::back()->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Deposit added!' : 'تمت الإضافة للهدف!',
        ]);
    }
}

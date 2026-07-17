<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SalaryController extends Controller
{
    public function edit(Request $request): Response
    {
        $setting = $request->user()->salarySetting;

        $incomeCategories = Category::where('type', 'income')
            ->whereNull('user_id')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]);

        return Inertia::render('settings/Salary', [
            'setting' => $setting ? [
                'amount' => $setting->amount,
                'day_of_month' => $setting->day_of_month,
                'is_active' => $setting->is_active,
            ] : [
                'amount' => 0,
                'day_of_month' => 1,
                'is_active' => false,
            ],
            'incomeCategories' => $incomeCategories,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'day_of_month' => 'required|integer|min:1|max:28',
            'is_active' => 'required|in:0,1',
        ]);

        $validated['is_active'] = (bool) $validated['is_active'];

        $request->user()->salarySetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return Redirect::back()->with('toast', [
            'type' => 'success',
            'message' => 'تم حفظ إعدادات الراتب بنجاح.',
        ]);
    }
}

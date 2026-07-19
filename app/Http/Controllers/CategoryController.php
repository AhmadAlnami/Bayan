<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
        ]);

        $request->user()->categories()->create($validated);

        return Redirect::back()->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en'
                ? 'Category added successfully.'
                : 'تمت إضافة الفئة بنجاح.',
        ]);
    }
}

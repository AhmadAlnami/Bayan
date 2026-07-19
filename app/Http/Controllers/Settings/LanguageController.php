<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Language', [
            'locale' => $request->user()->locale ?? 'ar',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return Redirect::back()->with('toast', [
            'type' => 'success',
            'message' => $validated['locale'] === 'ar'
                ? 'تم تغيير اللغة بنجاح.'
                : 'Language updated successfully.',
        ]);
    }
}

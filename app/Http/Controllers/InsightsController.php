<?php

namespace App\Http\Controllers;

use App\Services\InsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $insights = (new InsightsService)->calculate($request->user());

        return Inertia::render('Insights', [
            'insights' => $insights,
        ]);
    }
}

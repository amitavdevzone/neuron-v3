<?php

namespace App\Http\Controllers;

use App\Actions\QuoteCreateAction;
use App\Http\Requests\StoreTutorialQuoteRequest;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TutorialQuoteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('tutorial/demo-queue', [
            'users' => User::query()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'email']),
            'quotes' => Quote::query()
                ->with([
                    'product:id,name',
                    'user:id,name,email',
                ])
                ->latest()
                ->limit(10)
                ->get(['id', 'user_id', 'product_id', 'qty', 'created_at']),
        ]);
    }

    public function store(StoreTutorialQuoteRequest $request, QuoteCreateAction $action): RedirectResponse
    {
        $action->execute(
            (int) $request->validated('user_id'),
            (int) $request->validated('qty'),
        );

        return to_route('tutorial.demo-queue.index');
    }
}

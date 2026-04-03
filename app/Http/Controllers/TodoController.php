<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoStoreRequest;
use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('todos/index', [
            'todos' => Todo::query()
                ->orderByDesc('id')
                ->get(['id', 'task', 'is_complete']),
        ]);
    }

    public function store(TodoStoreRequest $request): RedirectResponse
    {
        sleep(3);

        Todo::query()->create([
            'task' => $request->validated('task'),
        ]);

        return to_route('todos.index');
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        $todo->delete();

        return to_route('todos.index');
    }
}

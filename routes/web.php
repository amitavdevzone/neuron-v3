<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserRegistrationController;
use App\Neuron\Workflows\VoteAcceptWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use NeuronAI\Laravel\Models\WorkflowInterrupt as WorkflowInterruptModel;
use NeuronAI\Workflow\Interrupt\Action;
use NeuronAI\Workflow\Interrupt\ApprovalRequest;
use NeuronAI\Workflow\Interrupt\WorkflowInterrupt;
use NeuronAI\Workflow\Persistence\EloquentPersistence;
use NeuronAI\Workflow\WorkflowState;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');

Route::post('tutorial/users', [UserRegistrationController::class, 'store'])->name('tutorial.users.store');

Route::controller(TodoController::class)->group(function () {
    Route::get('todos', 'index')->name('todos.index');
    Route::post('todos', 'store')->name('todos.store');
    Route::delete('todos/{todo}', 'destroy')->name('todos.destroy');
});

require __DIR__.'/settings.php';

Route::get('test', function () {
    $persistence = new EloquentPersistence(WorkflowInterruptModel::class);
    $state = new WorkflowState(['age' => 15]);

    $handler = VoteAcceptWorkflow::make(
        state: $state,
        persistence: $persistence,
    )->init();

    try {
        $finalState = $handler->run();

        return response()->json([
            'status' => 'completed',
            'state' => $finalState->all(),
        ]);
    } catch (WorkflowInterrupt $interrupt) {
        $request = $interrupt->getRequest();
        $resumeToken = $interrupt->getResumeToken();
        $actions = [];

        if ($request instanceof ApprovalRequest) {
            $actions = collect($request->getActions())
                ->map(fn (Action $action): array => [
                    'id' => $action->id,
                    'name' => $action->name,
                    'description' => $action->description,
                    'decision' => $action->decision->value,
                    'resumeUrl' => url()->to('test/resume').'?'.http_build_query([
                        'resumeToken' => $resumeToken,
                        'actionId' => $action->id,
                    ]),
                ])
                ->all();
        }

        return response()->json([
            'status' => 'interrupted',
            'resumeToken' => $resumeToken,
            'message' => $request->getMessage(),
            'actions' => $actions,
        ], 409);
    }
});

Route::get('test/resume', function (Request $request) {
    $validated = $request->validate([
        'resumeToken' => ['required', 'string'],
        'actionId' => ['required', 'string'],
    ]);

    $persistence = new EloquentPersistence(WorkflowInterruptModel::class);
    $persistedInterrupt = $persistence->load($validated['resumeToken']);
    $persistedRequest = $persistedInterrupt->getRequest();

    if (! $persistedRequest instanceof ApprovalRequest) {
        return response()->json([
            'message' => 'Only ApprovalRequest interruptions are supported for resume.',
        ], 422);
    }

    $resumeActions = collect($persistedRequest->getActions())
        ->map(fn (Action $action): Action => new Action(
            id: $action->id,
            name: $action->name,
            description: $action->description,
        ))
        ->values();

    /** @var Action|null $selectedAction */
    $selectedAction = $resumeActions->firstWhere('id', $validated['actionId']);

    if (! $selectedAction instanceof Action) {
        return response()->json([
            'message' => 'Invalid actionId for this resume token.',
        ], 422);
    }

    $selectedAction->approve('Approved by user');

    $resumeRequest = new ApprovalRequest(
        message: $persistedRequest->getMessage(),
        actions: $resumeActions->all(),
    );

    $finalState = VoteAcceptWorkflow::make(
        persistence: $persistence,
        resumeToken: $validated['resumeToken'],
    )->init($resumeRequest)->run();

    return response()->json([
        'status' => 'resumed',
        'state' => $finalState->all(),
    ]);
});

<?php

use App\Models\Todo;
use Inertia\Testing\AssertableInertia as Assert;

test('todos page displays todos sorted by id descending', function () {
    Todo::query()->create([
        'task' => 'First task',
        'is_complete' => false,
    ]);

    Todo::query()->create([
        'task' => 'Second task',
        'is_complete' => false,
    ]);

    $response = $this->get(route('todos.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('todos/index')
            ->where('todos.0.task', 'Second task')
            ->where('todos.1.task', 'First task'),
        );
});

test('todo can be created with default incomplete state', function () {
    $response = $this->post(route('todos.store'), [
        'task' => 'Write integration test',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('todos.index'));

    $todo = Todo::query()->first();

    expect($todo)->not->toBeNull();
    expect($todo?->task)->toBe('Write integration test');
    expect($todo?->is_complete)->toBeFalse();
});

test('todo can be deleted', function () {
    $todo = Todo::query()->create([
        'task' => 'Delete me',
        'is_complete' => false,
    ]);

    $response = $this->delete(route('todos.destroy', $todo));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('todos.index'));

    expect(Todo::query()->find($todo->id))->toBeNull();
});

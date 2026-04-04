import { Head, router, useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { destroy, index, store } from '@/routes/todos';
import type { BreadcrumbItem } from '@/types';
import type { FormEvent } from 'react';

type TodoItem = {
    id: number | null;
    task: string;
    is_complete: boolean;
    tempKey?: number;
};

type Props = {
    todos: TodoItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Todos',
        href: index(),
    },
];

export default function TodosPage({ todos }: Props) {
    const form = useForm({
        task: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const trimmedTask = form.data.task.trim();

        if (trimmedTask.length === 0) {
            return;
        }

        const tempKey = Date.now();

        form
            .optimistic((pageProps) => ({
                todos: [
                    {
                        id: null,
                        task: trimmedTask,
                        is_complete: false,
                        tempKey,
                    },
                    ...(pageProps as Props).todos,
                ],
            }))
            .post(store.url(), {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                },
            });
    };

    const removeTodo = (todoId: number) => {
        router
            .optimistic((pageProps) => ({
                todos: (pageProps as Props).todos.filter(
                    (todo) => todo.id !== todoId,
                ),
            }))
            .delete(destroy.url(todoId), {
                preserveScroll: true,
            });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Todos" />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 p-4">
                <form onSubmit={submit}>
                    <Input
                        name="task"
                        value={form.data.task}
                        onChange={(event) => form.setData('task', event.target.value)}
                        placeholder="Add a todo and press Enter"
                        aria-label="Todo task"
                        disabled={form.processing}
                    />
                    {form.errors.task ? (
                        <p className="mt-2 text-sm text-red-600">{form.errors.task}</p>
                    ) : null}
                </form>

                <div className="space-y-2">
                    {todos.map((todo) => (
                        <div
                            key={todo.id ?? `optimistic-${todo.tempKey ?? todo.task}`}
                            className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm"
                        >
                            <span>{todo.task}</span>
                            {todo.id !== null ? (
                                <button
                                    type="button"
                                    className="text-red-600 hover:underline"
                                    onClick={() => {
                                        if (todo.id !== null) {
                                            removeTodo(todo.id);
                                        }
                                    }}
                                >
                                    Delete
                                </button>
                            ) : null}
                        </div>
                    ))}

                    {todos.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No todos yet.
                        </p>
                    ) : null}
                </div>
            </div>
        </AppLayout>
    );
}

import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/tutorial/quotes';
import type { BreadcrumbItem } from '@/types';

type UserOption = {
    id: number;
    name: string;
    email: string;
};

type QuoteItem = {
    id: number;
    qty: number;
    created_at: string;
    user: UserOption;
    product: {
        id: number;
        name: string;
    };
};

type Props = {
    users: UserOption[];
    quotes: QuoteItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quotes',
        href: index(),
    },
];

export default function TutorialQuotesPage({ users, quotes }: Props) {
    const form = useForm({
        user_id: '',
        qty: '1',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('qty');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Request quote" />

            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Request a quote</CardTitle>
                        <CardDescription>
                            Select a user and quantity. The tutorial flow
                            stores product ID 1 automatically.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="user_id">User</Label>
                                <Select
                                    value={form.data.user_id}
                                    onValueChange={(value) =>
                                        form.setData('user_id', value)
                                    }
                                    disabled={users.length === 0}
                                >
                                    <SelectTrigger
                                        id="user_id"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            form.errors.user_id,
                                        )}
                                    >
                                        <SelectValue placeholder="Select a user" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.name} ({user.email})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.user_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="qty">Quantity</Label>
                                <Input
                                    id="qty"
                                    name="qty"
                                    type="number"
                                    min="1"
                                    value={form.data.qty}
                                    onChange={(event) =>
                                        form.setData('qty', event.target.value)
                                    }
                                    aria-invalid={Boolean(form.errors.qty)}
                                />
                                <InputError message={form.errors.qty} />
                            </div>

                            {users.length === 0 && (
                                <p className="text-muted-foreground text-sm">
                                    Create a tutorial user before requesting a
                                    quote.
                                </p>
                            )}

                            <div>
                                <Button
                                    type="submit"
                                    disabled={
                                        form.processing ||
                                        users.length === 0 ||
                                        form.data.user_id.length === 0
                                    }
                                >
                                    {form.processing
                                        ? 'Requesting...'
                                        : 'Request quote'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent quotes</CardTitle>
                        <CardDescription>
                            Latest quote requests created through this tutorial
                            flow.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {quotes.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No quotes have been requested yet.
                            </p>
                        ) : (
                            <div className="overflow-hidden rounded-md border">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium">
                                                ID
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                User
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Product
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium">
                                                Qty
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {quotes.map((quote) => (
                                            <tr key={quote.id}>
                                                <td className="px-4 py-3">
                                                    {quote.id}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {quote.user.name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {quote.product.name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {quote.qty}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

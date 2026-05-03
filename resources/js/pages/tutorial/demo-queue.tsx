import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
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
import {
    index as demoQueueIndex,
    store as storeQuote,
} from '@/routes/tutorial/demo-queue';
import { store as storeUser } from '@/routes/tutorial/users';
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

type UserFormErrors = Partial<Record<'name' | 'email', string>>;

type ValidationErrorResponse = {
    errors?: Partial<Record<'name' | 'email', string[]>>;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demo queue',
        href: demoQueueIndex(),
    },
];

const getXsrfToken = (): string | null => {
    const cookie = document.cookie
        .split('; ')
        .find((value) => value.startsWith('XSRF-TOKEN='));

    if (!cookie) {
        return null;
    }

    return decodeURIComponent(cookie.split('=')[1] ?? '');
};

const validationErrorsFrom = (
    response: ValidationErrorResponse,
): UserFormErrors => ({
    name: response.errors?.name?.[0],
    email: response.errors?.email?.[0],
});

export default function DemoQueuePage({ users, quotes }: Props) {
    const [userOptions, setUserOptions] = useState(users);
    const [userForm, setUserForm] = useState({
        name: '',
        email: '',
    });
    const [userFormErrors, setUserFormErrors] = useState<UserFormErrors>({});
    const [isCreatingUser, setIsCreatingUser] = useState(false);
    const [userCreatedMessage, setUserCreatedMessage] = useState<string | null>(
        null,
    );

    const quoteForm = useForm({
        user_id: '',
        qty: '1',
    });

    const submitUser = async (
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> => {
        event.preventDefault();

        setIsCreatingUser(true);
        setUserFormErrors({});
        setUserCreatedMessage(null);

        const xsrfToken = getXsrfToken();
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (xsrfToken !== null) {
            headers['X-XSRF-TOKEN'] = xsrfToken;
        }

        try {
            const response = await fetch(storeUser.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify(userForm),
            });

            if (response.status === 422) {
                const payload =
                    (await response.json()) as ValidationErrorResponse;
                setUserFormErrors(validationErrorsFrom(payload));

                return;
            }

            if (!response.ok) {
                setUserFormErrors({
                    email: 'Unable to create the user. Please try again.',
                });

                return;
            }

            const createdUser = (await response.json()) as UserOption;

            setUserOptions((currentUsers) =>
                [...currentUsers, createdUser].sort((firstUser, secondUser) =>
                    firstUser.name.localeCompare(secondUser.name),
                ),
            );
            setUserForm({ name: '', email: '' });
            setUserCreatedMessage(`${createdUser.name} was created.`);
            quoteForm.setData('user_id', createdUser.id.toString());
        } catch {
            setUserFormErrors({
                email: 'Unable to create the user. Please try again.',
            });
        } finally {
            setIsCreatingUser(false);
        }
    };

    const submitQuote = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        quoteForm.post(storeQuote.url(), {
            preserveScroll: true,
            onSuccess: () => {
                quoteForm.reset('qty');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Demo queue" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Create user</CardTitle>
                            <CardDescription>
                                Add a tutorial user. The backend creates the
                                account and publishes the registration event.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitUser} className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={userForm.name}
                                        onChange={(event) =>
                                            setUserForm((currentForm) => ({
                                                ...currentForm,
                                                name: event.target.value,
                                            }))
                                        }
                                        aria-invalid={Boolean(
                                            userFormErrors.name,
                                        )}
                                    />
                                    <InputError message={userFormErrors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value={userForm.email}
                                        onChange={(event) =>
                                            setUserForm((currentForm) => ({
                                                ...currentForm,
                                                email: event.target.value,
                                            }))
                                        }
                                        aria-invalid={Boolean(
                                            userFormErrors.email,
                                        )}
                                    />
                                    <InputError
                                        message={userFormErrors.email}
                                    />
                                </div>

                                {userCreatedMessage !== null && (
                                    <p className="text-sm font-medium text-green-600">
                                        {userCreatedMessage}
                                    </p>
                                )}

                                <div>
                                    <Button
                                        type="submit"
                                        disabled={isCreatingUser}
                                    >
                                        {isCreatingUser
                                            ? 'Creating...'
                                            : 'Create'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Request a quote</CardTitle>
                            <CardDescription>
                                Select a user and quantity. The tutorial flow
                                stores product ID 1 automatically.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitQuote} className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="user_id">User</Label>
                                    <Select
                                        value={quoteForm.data.user_id}
                                        onValueChange={(value) =>
                                            quoteForm.setData('user_id', value)
                                        }
                                        disabled={userOptions.length === 0}
                                    >
                                        <SelectTrigger
                                            id="user_id"
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                quoteForm.errors.user_id,
                                            )}
                                        >
                                            <SelectValue placeholder="Select a user" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {userOptions.map((user) => (
                                                <SelectItem
                                                    key={user.id}
                                                    value={user.id.toString()}
                                                >
                                                    {user.name} ({user.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={quoteForm.errors.user_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="qty">Quantity</Label>
                                    <Input
                                        id="qty"
                                        name="qty"
                                        type="number"
                                        min="1"
                                        value={quoteForm.data.qty}
                                        onChange={(event) =>
                                            quoteForm.setData(
                                                'qty',
                                                event.target.value,
                                            )
                                        }
                                        aria-invalid={Boolean(
                                            quoteForm.errors.qty,
                                        )}
                                    />
                                    <InputError
                                        message={quoteForm.errors.qty}
                                    />
                                </div>

                                {userOptions.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Create a tutorial user before requesting
                                        a quote.
                                    </p>
                                )}

                                <div>
                                    <Button
                                        type="submit"
                                        disabled={
                                            quoteForm.processing ||
                                            userOptions.length === 0 ||
                                            quoteForm.data.user_id.length === 0
                                        }
                                    >
                                        {quoteForm.processing
                                            ? 'Requesting...'
                                            : 'Request'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

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
                            <p className="text-sm text-muted-foreground">
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

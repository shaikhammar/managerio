import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { Account, BreadcrumbItem, JournalEntry } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Opening Balances', href: '/accounting/opening-balances/create' },
];

type AccountWithType = Pick<Account, 'id' | 'code' | 'name' | 'type' | 'sub_type'>;

type Props = {
    accounts: AccountWithType[];
    existingEntry: JournalEntry | null;
};

const TYPE_ORDER = ['asset', 'liability', 'equity', 'revenue', 'expense'] as const;

export default function OpeningBalancesCreate({ accounts, existingEntry }: Props) {
    const { format } = useCurrency();

    const grouped = TYPE_ORDER.map((type) => ({
        type,
        accounts: accounts.filter((a) => a.type === type),
    })).filter((g) => g.accounts.length > 0);

    const initialLines: Record<number, string> = {};
    accounts.forEach((a) => {
        initialLines[a.id] = '';
    });

    const [balances, setBalances] = useState<Record<number, string>>(initialLines);

    const { data, setData, processing, errors } = useForm({
        date: new Date().toISOString().split('T')[0],
        description: 'Opening balances',
        lines: [] as { account_id: number; balance: number }[],
    });

    function setBalance(accountId: number, value: string) {
        setBalances((prev) => ({ ...prev, [accountId]: value }));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        const lines = Object.entries(balances)
            .filter(([, v]) => v !== '' && parseFloat(v) !== 0)
            .map(([id, v]) => ({ account_id: parseInt(id), balance: parseFloat(v) }));

        router.post('/accounting/opening-balances', {
            date: data.date,
            description: data.description,
            lines,
        });
    }

    const totalEntered = Object.values(balances)
        .filter((v) => v !== '')
        .reduce((s, v) => s + Math.abs(parseFloat(v) || 0), 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Opening Balances" />
            <div className="max-w-4xl mx-auto p-4 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Opening Balances</h1>
                        <p className="text-muted-foreground text-sm mt-1">
                            Enter the starting balance for each account when migrating from another system.
                        </p>
                    </div>

                    {existingEntry && (
                        <div className="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-800 dark:text-amber-300">
                            <p className="font-medium">Existing opening balances found</p>
                            <p className="mt-1">
                                Opening balances were already posted on {existingEntry.date}.{' '}
                                <Link
                                    href={`/accounting/journal-entries/${existingEntry.id}`}
                                    className="underline decoration-dotted"
                                >
                                    View entry
                                </Link>
                                . Submitting this form will create an additional entry.
                            </p>
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>As of Date</CardTitle>
                            <CardDescription>The date these balances are effective from</CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="date">Date *</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={data.date}
                                    onChange={(e) => setData('date', e.target.value)}
                                />
                                <InputError message={errors.date} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Input
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    {grouped.map(({ type, accounts: groupAccounts }) => (
                        <Card key={type}>
                            <CardHeader>
                                <CardTitle className="capitalize">{type} Accounts</CardTitle>
                                <CardDescription>
                                    {type === 'asset' || type === 'expense'
                                        ? 'Enter the debit (positive) balance for each account'
                                        : 'Enter the credit (positive) balance for each account'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {groupAccounts.map((account) => (
                                        <div key={account.id} className="grid grid-cols-[1fr_160px] gap-3 items-center">
                                            <div>
                                                <p className="text-sm font-medium">{account.name}</p>
                                                <p className="text-xs font-mono text-muted-foreground">{account.code}</p>
                                            </div>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="text-right"
                                                placeholder="0.00"
                                                value={balances[account.id] ?? ''}
                                                onChange={(e) => setBalance(account.id, e.target.value)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    ))}

                    <InputError message={errors.lines} />

                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            {totalEntered > 0 && <>Total entered: <span className="font-medium">{format(totalEntered)}</span></>}
                        </p>
                        <div className="flex gap-3">
                            <Button variant="outline" type="button" asChild>
                                <Link href="/accounting/journal-entries">Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing || totalEntered === 0}>
                                {processing ? 'Posting...' : 'Post Opening Balances'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

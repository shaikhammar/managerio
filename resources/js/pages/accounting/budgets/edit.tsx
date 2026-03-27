import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { Account, AccountBudget, BreadcrumbItem } from '@/types';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Budgets', href: '/accounting/budgets' },
    { title: 'Edit', href: '/accounting/budgets/edit' },
];

type Props = {
    accounts: Pick<Account, 'id' | 'code' | 'name' | 'type'>[];
    existingBudgets: AccountBudget[];
    year: number;
};

type BudgetGrid = Record<number, Record<number, string>>;

function buildInitialGrid(accounts: Props['accounts'], budgets: AccountBudget[]): BudgetGrid {
    const grid: BudgetGrid = {};
    accounts.forEach((a) => {
        grid[a.id] = {};

        for (let m = 1; m <= 12; m++) {
            grid[a.id][m] = '';
        }
    });
    budgets.forEach((b) => {
        if (b.month !== null && grid[b.account_id]) {
            grid[b.account_id][b.month] = b.amount > 0 ? b.amount.toString() : '';
        }
    });

    return grid;
}

export default function BudgetEdit({ accounts, existingBudgets, year }: Props) {
    const [grid, setGrid] = useState<BudgetGrid>(() => buildInitialGrid(accounts, existingBudgets));
    const [saving, setSaving] = useState(false);

    function changeYear(delta: number) {
        router.get('/accounting/budgets/edit', { year: year + delta });
    }

    function setCell(accountId: number, month: number, value: string) {
        setGrid((prev) => ({
            ...prev,
            [accountId]: { ...prev[accountId], [month]: value },
        }));
    }

    function handleSave() {
        setSaving(true);

        const entries: { account_id: number; month: number; amount: number }[] = [];
        Object.entries(grid).forEach(([accountId, months]) => {
            Object.entries(months).forEach(([month, value]) => {
                entries.push({
                    account_id: parseInt(accountId),
                    month: parseInt(month),
                    amount: parseFloat(value) || 0,
                });
            });
        });

        router.post('/accounting/budgets', {
            year,
            entries,
        }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    }

    const grouped = (['revenue', 'expense'] as const).map((type) => ({
        type,
        accounts: accounts.filter((a) => a.type === type),
    })).filter((g) => g.accounts.length > 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Budget ${year}`} />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Edit Budget</h1>
                        <p className="text-muted-foreground text-sm">Set monthly budget targets for revenue and expense accounts</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-1">
                            <Button variant="outline" size="sm" onClick={() => changeYear(-1)}>‹</Button>
                            <span className="px-3 text-sm font-medium">{year}</span>
                            <Button variant="outline" size="sm" onClick={() => changeYear(1)}>›</Button>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={`/accounting/budgets?year=${year}`}>Cancel</Link>
                        </Button>
                        <Button onClick={handleSave} disabled={saving}>
                            {saving ? 'Saving...' : 'Save Budget'}
                        </Button>
                    </div>
                </div>

                {grouped.map(({ type, accounts: groupAccounts }) => (
                    <Card key={type}>
                        <CardHeader>
                            <CardTitle className="capitalize">{type} Accounts</CardTitle>
                        </CardHeader>
                        <CardContent className="overflow-x-auto p-0">
                            <table className="w-full text-sm min-w-[900px]">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="text-left py-2 px-3 font-medium text-muted-foreground w-[180px]">Account</th>
                                        {MONTHS.map((m) => (
                                            <th key={m} className="text-center py-2 px-1 font-medium text-muted-foreground w-[72px]">{m}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {groupAccounts.map((account) => (
                                        <tr key={account.id} className="border-b last:border-0">
                                            <td className="py-2 px-3">
                                                <p className="font-medium">{account.name}</p>
                                                <p className="font-mono text-[10px] text-muted-foreground">{account.code}</p>
                                            </td>
                                            {Array.from({ length: 12 }, (_, i) => i + 1).map((month) => (
                                                <td key={month} className="py-1 px-1">
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        className="h-7 text-right text-xs px-1.5"
                                                        placeholder="0"
                                                        value={grid[account.id]?.[month] ?? ''}
                                                        onChange={(e) => setCell(account.id, month, e.target.value)}
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                ))}

                <div className="flex justify-end gap-3">
                    <Button variant="outline" asChild>
                        <Link href={`/accounting/budgets?year=${year}`}>Cancel</Link>
                    </Button>
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? 'Saving...' : 'Save Budget'}
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

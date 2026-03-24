import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BreadcrumbItem, AccountOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Account Transactions', href: '/reports/account-transactions' },
];

type Transaction = {
    id: number;
    date: string;
    entry_number: string;
    description: string | null;
    contact: string | null;
    debit: number;
    credit: number;
    balance: number;
};

type Report = {
    account: { id: number; code: string; name: string; type: string };
    period: { start: string; end: string };
    opening_balance: number;
    closing_balance: number;
    transactions: Transaction[];
};

type Props = {
    accounts: AccountOption[];
    report: Report | null;
    filters: { account_id?: string; start_date: string; end_date: string };
};

export default function AccountTransactions({ accounts, report, filters }: Props) {
    const { format } = useCurrency();
    const [accountId, setAccountId] = useState(filters.account_id || '');
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/account-transactions', { account_id: accountId || undefined, start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Account Transactions" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Account Transactions</h1>
                    <p className="text-muted-foreground text-sm">All transactions for a single account with running balance</p>
                </div>

                {/* Filters */}
                <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-3">
                    <div className="space-y-1 w-64">
                        <Label htmlFor="account_id" className="text-xs">Account</Label>
                        <Select value={accountId} onValueChange={setAccountId}>
                            <SelectTrigger id="account_id">
                                <SelectValue placeholder="Select account..." />
                            </SelectTrigger>
                            <SelectContent>
                                {accounts.map((a) => (
                                    <SelectItem key={a.id} value={a.id.toString()}>
                                        {a.code} · {a.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="start_date" className="text-xs">From</Label>
                        <Input id="start_date" type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} className="w-40" />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="end_date" className="text-xs">To</Label>
                        <Input id="end_date" type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} className="w-40" />
                    </div>
                    <Button type="submit" variant="outline" size="sm">Apply</Button>
                </form>

                {/* Report */}
                {!report ? (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <p className="text-muted-foreground">Select an account to view its transactions.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h2 className="font-semibold">{report.account.code} · {report.account.name}</h2>
                                    <p className="text-sm text-muted-foreground">{report.period.start} to {report.period.end}</p>
                                </div>
                            </div>

                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-muted-foreground">
                                        <th className="text-left py-2 w-28">Date</th>
                                        <th className="text-left py-2 w-28">Reference</th>
                                        <th className="text-left py-2">Description</th>
                                        <th className="text-left py-2 w-32">Contact</th>
                                        <th className="text-right py-2 w-28">Debit</th>
                                        <th className="text-right py-2 w-28">Credit</th>
                                        <th className="text-right py-2 w-28">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {/* Opening balance row */}
                                    <tr className="border-b bg-muted/30">
                                        <td className="py-2 text-muted-foreground">{report.period.start}</td>
                                        <td className="py-2"></td>
                                        <td className="py-2 text-muted-foreground italic">Opening Balance</td>
                                        <td className="py-2"></td>
                                        <td className="py-2"></td>
                                        <td className="py-2"></td>
                                        <td className="py-2 text-right font-medium">{format(report.opening_balance)}</td>
                                    </tr>

                                    {report.transactions.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="py-8 text-center text-muted-foreground">
                                                No transactions in this period
                                            </td>
                                        </tr>
                                    ) : (
                                        report.transactions.map((tx) => (
                                            <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/20">
                                                <td className="py-2 text-muted-foreground">{tx.date}</td>
                                                <td className="py-2 font-mono text-xs text-muted-foreground">{tx.entry_number}</td>
                                                <td className="py-2">{tx.description || '—'}</td>
                                                <td className="py-2 text-muted-foreground">{tx.contact || '—'}</td>
                                                <td className="py-2 text-right">{tx.debit > 0 ? format(tx.debit) : ''}</td>
                                                <td className="py-2 text-right">{tx.credit > 0 ? format(tx.credit) : ''}</td>
                                                <td className={`py-2 text-right font-medium ${tx.balance < 0 ? 'text-red-600 dark:text-red-400' : ''}`}>
                                                    {format(Math.abs(tx.balance))}{tx.balance < 0 ? ' CR' : ''}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t-2 font-bold">
                                        <td colSpan={6} className="py-2">Closing Balance</td>
                                        <td className={`py-2 text-right ${report.closing_balance < 0 ? 'text-red-600 dark:text-red-400' : ''}`}>
                                            {format(Math.abs(report.closing_balance))}{report.closing_balance < 0 ? ' CR' : ''}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, TrialBalanceEntry } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Trial Balance', href: '/reports/trial-balance' },
];

type Props = {
    trialBalance: TrialBalanceEntry[];
    filters: { as_of_date: string };
};

export default function TrialBalance({ trialBalance, filters }: Props) {
    const { format } = useCurrency();
    const [asOfDate, setAsOfDate] = useState(filters.as_of_date);
    const totalDebit = trialBalance.reduce((sum, e) => sum + e.debit, 0);
    const totalCredit = trialBalance.reduce((sum, e) => sum + e.credit, 0);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/trial-balance', { as_of_date: asOfDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trial Balance" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Trial Balance</h1>
                    <p className="text-muted-foreground text-sm">As of {filters.as_of_date}</p>
                </div>

                <form onSubmit={handleFilter} className="flex items-end gap-3">
                    <div className="space-y-1">
                        <Label htmlFor="as_of_date" className="text-xs">As of Date</Label>
                        <Input id="as_of_date" type="date" value={asOfDate} onChange={(e) => setAsOfDate(e.target.value)} className="w-40" />
                    </div>
                    <Button type="submit" variant="outline" size="sm">Apply</Button>
                </form>

                <Card>
                    <CardContent className="pt-6">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Code</th>
                                    <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Account</th>
                                    <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Debit</th>
                                    <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trialBalance.length === 0 ? (
                                    <tr><td colSpan={4} className="py-8 text-center text-muted-foreground">No balances to display</td></tr>
                                ) : (
                                    trialBalance.map((entry) => (
                                        <tr key={entry.account.id} className="border-b last:border-0">
                                            <td className="py-2 px-4 font-mono text-sm text-muted-foreground">{entry.account.code}</td>
                                            <td className="py-2 px-4 text-sm">{entry.account.name}</td>
                                            <td className="py-2 px-4 text-right text-sm font-medium">{entry.debit > 0 ? format(entry.debit) : ''}</td>
                                            <td className="py-2 px-4 text-right text-sm font-medium">{entry.credit > 0 ? format(entry.credit) : ''}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 font-bold">
                                    <td colSpan={2} className="py-3 px-4">Totals</td>
                                    <td className="py-3 px-4 text-right">{format(totalDebit)}</td>
                                    <td className="py-3 px-4 text-right">{format(totalCredit)}</td>
                                </tr>
                            </tfoot>
                        </table>
                        {totalDebit !== totalCredit && (
                            <div className="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-400">
                                ⚠️ Trial balance does not balance! Difference: {format(Math.abs(totalDebit - totalCredit))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

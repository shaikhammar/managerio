import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, BalanceSheetReport } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Balance Sheet', href: '/reports/balance-sheet' },
];

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(amount);
}

type Props = {
    report: BalanceSheetReport;
    filters: { as_of_date: string };
};

function Section({ title, color, accounts, total }: { title: string; color: string; accounts: { id: number; code: string; name: string; balance: number }[]; total: number }) {
    return (
        <div className="mb-6">
            <h3 className={`text-sm font-semibold uppercase tracking-wide mb-3 ${color}`}>{title}</h3>
            {accounts.length === 0 ? (
                <p className="text-sm text-muted-foreground py-2 pl-4">No accounts</p>
            ) : (
                <table className="w-full">
                    <tbody>
                        {accounts.map((account) => (
                            <tr key={account.id} className="border-b last:border-0">
                                <td className="py-2 pl-4 text-sm">
                                    <span className="text-muted-foreground mr-3">{account.code}</span>
                                    {account.name}
                                </td>
                                <td className="py-2 pr-4 text-right text-sm font-medium">{formatCurrency(account.balance)}</td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr className="border-t-2 font-bold">
                            <td className="py-2 pl-4">Total {title}</td>
                            <td className={`py-2 pr-4 text-right ${color}`}>{formatCurrency(total)}</td>
                        </tr>
                    </tfoot>
                </table>
            )}
        </div>
    );
}

export default function BalanceSheet({ report, filters }: Props) {
    const [asOfDate, setAsOfDate] = useState(filters.as_of_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/balance-sheet', { as_of_date: asOfDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Balance Sheet" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Balance Sheet</h1>
                    <p className="text-muted-foreground text-sm">As of {report.as_of}</p>
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
                        <Section title="Assets" color="text-blue-700 dark:text-blue-400" accounts={report.assets.accounts} total={report.assets.total} />
                        <Section title="Liabilities" color="text-orange-700 dark:text-orange-400" accounts={report.liabilities.accounts} total={report.liabilities.total} />
                        <Section title="Equity" color="text-purple-700 dark:text-purple-400" accounts={report.equity.accounts} total={report.equity.total} />

                        <div className="border-t-4 border-double pt-4 mt-4">
                            <div className="flex items-center justify-between px-4">
                                <span className="text-lg font-bold">Total Liabilities & Equity</span>
                                <span className="text-xl font-bold">{formatCurrency(report.total_liabilities_and_equity)}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ProfitAndLossReport } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Profit & Loss', href: '/reports/profit-and-loss' },
];

type Props = {
    report: ProfitAndLossReport;
    filters: { start_date: string; end_date: string };
};

export default function ProfitAndLoss({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/profit-and-loss', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profit & Loss" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Profit & Loss Statement</h1>
                        <p className="text-muted-foreground text-sm">
                            {report.period.start} to {report.period.end}
                        </p>
                    </div>
                </div>

                {/* Date Filter */}
                <form onSubmit={handleFilter} className="flex items-end gap-3">
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
                <Card>
                    <CardContent className="pt-6">
                        {/* Revenue */}
                        <div className="mb-6">
                            <h3 className="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide mb-3">Revenue</h3>
                            {report.revenue.accounts.length === 0 ? (
                                <p className="text-sm text-muted-foreground py-2 pl-4">No revenue recorded</p>
                            ) : (
                                <table className="w-full">
                                    <tbody>
                                        {report.revenue.accounts.map((account) => (
                                            <tr key={account.id} className="border-b last:border-0">
                                                <td className="py-2 pl-4 text-sm">
                                                    <span className="text-muted-foreground mr-3">{account.code}</span>
                                                    {account.name}
                                                </td>
                                                <td className="py-2 pr-4 text-right text-sm font-medium">{format(account.balance)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 font-bold">
                                            <td className="py-2 pl-4">Total Revenue</td>
                                            <td className="py-2 pr-4 text-right text-emerald-700 dark:text-emerald-400">{format(report.revenue.total)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            )}
                        </div>

                        {/* Expenses */}
                        <div className="mb-6">
                            <h3 className="text-sm font-semibold text-red-700 dark:text-red-400 uppercase tracking-wide mb-3">Expenses</h3>
                            {report.expenses.accounts.length === 0 ? (
                                <p className="text-sm text-muted-foreground py-2 pl-4">No expenses recorded</p>
                            ) : (
                                <table className="w-full">
                                    <tbody>
                                        {report.expenses.accounts.map((account) => (
                                            <tr key={account.id} className="border-b last:border-0">
                                                <td className="py-2 pl-4 text-sm">
                                                    <span className="text-muted-foreground mr-3">{account.code}</span>
                                                    {account.name}
                                                </td>
                                                <td className="py-2 pr-4 text-right text-sm font-medium">{format(account.balance)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 font-bold">
                                            <td className="py-2 pl-4">Total Expenses</td>
                                            <td className="py-2 pr-4 text-right text-red-700 dark:text-red-400">{format(report.expenses.total)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            )}
                        </div>

                        {/* Net Profit */}
                        <div className="border-t-4 border-double pt-4">
                            <div className="flex items-center justify-between px-4">
                                <span className="text-lg font-bold">Net Profit</span>
                                <span className={`text-xl font-bold ${report.net_profit >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}`}>
                                    {format(report.net_profit)}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

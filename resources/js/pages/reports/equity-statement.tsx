import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Changes in Equity', href: '/reports/equity-statement' },
];

type EquityAccount = {
    id: number;
    code: string;
    name: string;
    opening_balance: number;
    movement: number;
    closing_balance: number;
};

type EquityReport = {
    period: { start: string; end: string };
    accounts: EquityAccount[];
    net_income: number;
    total_opening: number;
    total_closing: number;
};

type Props = {
    report: EquityReport;
    filters: { start_date: string; end_date: string };
};

export default function EquityStatement({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/equity-statement', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Statement of Changes in Equity" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Statement of Changes in Equity</h1>
                        <p className="text-muted-foreground text-sm">{report.period.start} to {report.period.end}</p>
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

                <Card>
                    <CardContent className="pt-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-muted-foreground">
                                    <th className="text-left py-2 pl-4">Account</th>
                                    <th className="text-right py-2 w-36">Opening Balance</th>
                                    <th className="text-right py-2 w-36">Movement</th>
                                    <th className="text-right py-2 pr-4 w-36">Closing Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.accounts.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-8 text-center text-muted-foreground">No equity accounts found</td>
                                    </tr>
                                ) : (
                                    report.accounts.map((account) => (
                                        <tr key={account.id} className="border-b last:border-0">
                                            <td className="py-3 pl-4">
                                                <span className="text-muted-foreground mr-3">{account.code}</span>
                                                {account.name}
                                            </td>
                                            <td className="py-3 text-right">{format(account.opening_balance)}</td>
                                            <td className={`py-3 text-right ${account.movement < 0 ? 'text-red-600 dark:text-red-400' : account.movement > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'}`}>
                                                {account.movement === 0 ? '—' : (account.movement < 0 ? `(${format(Math.abs(account.movement))})` : format(account.movement))}
                                            </td>
                                            <td className="py-3 text-right pr-4 font-medium">{format(account.closing_balance)}</td>
                                        </tr>
                                    ))
                                )}

                                {/* Net Income row */}
                                <tr className="border-b bg-emerald-50/50 dark:bg-emerald-950/20">
                                    <td className="py-3 pl-4 text-emerald-700 dark:text-emerald-400 font-medium">Net Income for Period</td>
                                    <td className="py-3 text-right text-muted-foreground">—</td>
                                    <td className={`py-3 text-right font-medium ${report.net_income < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'}`}>
                                        {report.net_income === 0 ? '—' : (report.net_income < 0 ? `(${format(Math.abs(report.net_income))})` : format(report.net_income))}
                                    </td>
                                    <td className="py-3 text-right pr-4">—</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 font-bold text-base">
                                    <td className="py-3 pl-4">Total Equity</td>
                                    <td className="py-3 text-right">{format(report.total_opening)}</td>
                                    <td className="py-3 text-right"></td>
                                    <td className="py-3 text-right pr-4 text-emerald-700 dark:text-emerald-400">{format(report.total_closing)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

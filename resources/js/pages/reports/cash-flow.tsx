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
    { title: 'Cash Flow Statement', href: '/reports/cash-flow' },
];

type CashFlowReport = {
    period: { start: string; end: string };
    operating: {
        net_income: number;
        depreciation: number;
        change_in_receivables: number;
        change_in_payables: number;
        change_in_tax_receivable: number;
        change_in_tax_payable: number;
        total: number;
    };
    investing: {
        change_in_fixed_assets: number;
        total: number;
    };
    financing: {
        change_in_equity: number;
        total: number;
    };
    net_change: number;
    opening_cash: number;
    closing_cash: number;
};

type Props = {
    report: CashFlowReport;
    filters: { start_date: string; end_date: string };
};

function Row({ label, value, indent = false, bold = false, positive }: { label: string; value: number; indent?: boolean; bold?: boolean; positive?: boolean }) {
    const { format } = useCurrency();
    const color = positive !== undefined
        ? (positive ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400')
        : (value >= 0 ? '' : 'text-red-600 dark:text-red-400');

    return (
        <tr className={bold ? 'border-t font-bold' : 'border-b last:border-0'}>
            <td className={`py-2 text-sm ${indent ? 'pl-8' : 'pl-4'} ${bold ? 'text-base' : ''}`}>{label}</td>
            <td className={`py-2 pr-4 text-right text-sm ${bold ? 'text-base' : ''} ${color}`}>
                {value !== 0 ? (value < 0 ? `(${format(Math.abs(value))})` : format(value)) : '—'}
            </td>
        </tr>
    );
}

export default function CashFlow({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/reports/cash-flow', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cash Flow Statement" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Cash Flow Statement</h1>
                        <p className="text-muted-foreground text-sm">{report.period.start} to {report.period.end} · Indirect Method</p>
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
                        {/* Operating Activities */}
                        <h3 className="text-sm font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-2 px-4">Operating Activities</h3>
                        <table className="w-full mb-4">
                            <tbody>
                                <Row label="Net Income" value={report.operating.net_income} indent />
                                {report.operating.depreciation !== 0 && (
                                    <Row label="Add: Depreciation & Amortisation" value={report.operating.depreciation} indent />
                                )}
                                {report.operating.change_in_receivables !== 0 && (
                                    <Row label="Change in Accounts Receivable" value={report.operating.change_in_receivables} indent />
                                )}
                                {report.operating.change_in_payables !== 0 && (
                                    <Row label="Change in Accounts Payable" value={report.operating.change_in_payables} indent />
                                )}
                                {report.operating.change_in_tax_receivable !== 0 && (
                                    <Row label="Change in Tax Receivable" value={report.operating.change_in_tax_receivable} indent />
                                )}
                                {report.operating.change_in_tax_payable !== 0 && (
                                    <Row label="Change in Tax Payable" value={report.operating.change_in_tax_payable} indent />
                                )}
                                <Row label="Net Cash from Operating Activities" value={report.operating.total} bold positive={report.operating.total >= 0} />
                            </tbody>
                        </table>

                        {/* Investing Activities */}
                        <h3 className="text-sm font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wide mb-2 px-4 mt-4">Investing Activities</h3>
                        <table className="w-full mb-4">
                            <tbody>
                                {report.investing.change_in_fixed_assets !== 0 && (
                                    <Row label="Change in Fixed Assets" value={report.investing.change_in_fixed_assets} indent />
                                )}
                                {report.investing.change_in_fixed_assets === 0 && (
                                    <tr><td colSpan={2} className="py-2 pl-8 text-sm text-muted-foreground">No investing activity</td></tr>
                                )}
                                <Row label="Net Cash from Investing Activities" value={report.investing.total} bold positive={report.investing.total >= 0} />
                            </tbody>
                        </table>

                        {/* Financing Activities */}
                        <h3 className="text-sm font-semibold text-orange-700 dark:text-orange-400 uppercase tracking-wide mb-2 px-4 mt-4">Financing Activities</h3>
                        <table className="w-full mb-4">
                            <tbody>
                                {report.financing.change_in_equity !== 0 && (
                                    <Row label="Owner Contributions / Withdrawals" value={report.financing.change_in_equity} indent />
                                )}
                                {report.financing.change_in_equity === 0 && (
                                    <tr><td colSpan={2} className="py-2 pl-8 text-sm text-muted-foreground">No financing activity</td></tr>
                                )}
                                <Row label="Net Cash from Financing Activities" value={report.financing.total} bold positive={report.financing.total >= 0} />
                            </tbody>
                        </table>

                        {/* Summary */}
                        <div className="border-t-4 border-double pt-4 space-y-2">
                            <div className="flex justify-between px-4 text-sm">
                                <span className="text-muted-foreground">Opening Cash & Bank Balance</span>
                                <span className="font-medium">{format(report.opening_cash)}</span>
                            </div>
                            <div className="flex justify-between px-4 text-sm">
                                <span className="text-muted-foreground">Net Change in Cash</span>
                                <span className={`font-medium ${report.net_change < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'}`}>
                                    {report.net_change < 0 ? `(${format(Math.abs(report.net_change))})` : format(report.net_change)}
                                </span>
                            </div>
                            <div className="flex justify-between px-4 font-bold text-lg border-t pt-2">
                                <span>Closing Cash & Bank Balance</span>
                                <span className={report.closing_cash < 0 ? 'text-red-700 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400'}>
                                    {format(report.closing_cash)}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

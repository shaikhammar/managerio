import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Reports', href: '/translation/reports' },
    { title: 'Average Margin', href: '/translation/reports/average-margin' },
];

type Report = {
    period: { start: string; end: string };
    project_count: number;
    total_revenue: number;
    total_cost: number;
    gross_margin: number;
    margin_pct: number;
};

type Props = {
    report: Report;
    filters: { start_date: string; end_date: string };
};

function marginColor(pct: number): string {
    if (pct >= 40) {
return 'text-emerald-600 dark:text-emerald-400';
}

    if (pct >= 20) {
return 'text-amber-600 dark:text-amber-400';
}

    return 'text-red-600 dark:text-red-400';
}

export default function AverageMargin({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/reports/average-margin', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Average Margin" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-2xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Average Margin</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Gross margin across all projects for the selected period
                    </p>
                </div>

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

                <div className="grid grid-cols-2 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Projects in Period</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{report.project_count}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Gross Margin %</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className={`text-2xl font-bold ${marginColor(report.margin_pct)}`}>{report.margin_pct}%</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <table className="w-full text-sm">
                            <tbody className="divide-y">
                                <tr>
                                    <td className="py-3 text-muted-foreground">Total Revenue</td>
                                    <td className="py-3 text-right font-medium tabular-nums">{format(report.total_revenue)}</td>
                                </tr>
                                <tr>
                                    <td className="py-3 text-muted-foreground">Total Translator Cost</td>
                                    <td className="py-3 text-right tabular-nums text-red-600 dark:text-red-400">({format(report.total_cost)})</td>
                                </tr>
                                <tr className="font-semibold text-base">
                                    <td className="py-3">Gross Margin</td>
                                    <td className={`py-3 text-right tabular-nums ${marginColor(report.margin_pct)}`}>{format(report.gross_margin)}</td>
                                </tr>
                                <tr>
                                    <td className="py-3 text-muted-foreground">Margin %</td>
                                    <td className={`py-3 text-right font-semibold tabular-nums ${marginColor(report.margin_pct)}`}>{report.margin_pct}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

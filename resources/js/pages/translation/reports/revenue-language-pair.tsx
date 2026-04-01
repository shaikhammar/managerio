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
    { title: 'Revenue by Language Pair', href: '/translation/reports/revenue-language-pair' },
];

type Row = {
    language_pair: string;
    source_name: string;
    target_name: string;
    project_count: number;
    word_count: number;
    revenue: number;
    revenue_pct: number;
};

type Report = {
    period: { start: string; end: string };
    rows: Row[];
    totals: { revenue: number };
};

type Props = {
    report: Report;
    filters: { start_date: string; end_date: string };
};

export default function RevenueByLanguagePair({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/reports/revenue-language-pair', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Revenue by Language Pair" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Revenue by Language Pair</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Revenue per language combination — based on project target pricing
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
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{format(report.totals.revenue)}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Language Pairs</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{report.rows.length}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardContent className="pt-4">
                        {report.rows.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">No data for this period.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-3 font-medium text-muted-foreground">Language Pair</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Projects</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Words</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Revenue</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {report.rows.map((row) => (
                                            <tr key={row.language_pair} className="hover:bg-muted/40 transition-colors">
                                                <td className="py-3 pr-4">
                                                    <div className="font-medium">{row.language_pair}</div>
                                                    <div className="text-xs text-muted-foreground">{row.source_name} → {row.target_name}</div>
                                                </td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{row.project_count}</td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{row.word_count.toLocaleString()}</td>
                                                <td className="py-3 pr-4 text-right tabular-nums font-medium">{format(row.revenue)}</td>
                                                <td className="py-3 text-right tabular-nums text-muted-foreground">{row.revenue_pct}%</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t font-semibold">
                                            <td className="pt-3" colSpan={3}>Total</td>
                                            <td className="pt-3 text-right tabular-nums">{format(report.totals.revenue)}</td>
                                            <td className="pt-3 text-right">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

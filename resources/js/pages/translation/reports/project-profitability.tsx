import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
    { title: 'Project Profitability', href: '/translation/reports/project-profitability' },
];

type ProjectRow = {
    id: number;
    name: string;
    reference: string | null;
    client: string;
    status: string;
    status_label: string;
    deadline: string | null;
    revenue: number;
    cost: number;
    margin: number;
    margin_pct: number;
};

type Report = {
    period: { start: string; end: string };
    projects: ProjectRow[];
    totals: { revenue: number; cost: number; margin: number; margin_pct: number };
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

export default function ProjectProfitability({ report, filters }: Props) {
    const { format } = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/reports/project-profitability', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project Profitability" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Project Profitability</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Revenue vs translator costs per project — filtered by project deadline
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

                {/* Summary cards */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {[
                        { label: 'Total Revenue', value: format(report.totals.revenue) },
                        { label: 'Total Cost', value: format(report.totals.cost) },
                        { label: 'Gross Margin', value: format(report.totals.margin) },
                        { label: 'Margin %', value: `${report.totals.margin_pct}%` },
                    ].map((s) => (
                        <Card key={s.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">{s.label}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold">{s.value}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="pt-4">
                        {report.projects.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">No projects with deadlines in this period.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-3 font-medium text-muted-foreground">Project</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Client</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Status</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Revenue</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Cost</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Margin</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Margin %</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {report.projects.map((row) => (
                                            <tr key={row.id} className="hover:bg-muted/40 transition-colors">
                                                <td className="py-3 pr-4">
                                                    <div className="font-medium">{row.name}</div>
                                                    {row.reference && <div className="text-muted-foreground text-xs">{row.reference}</div>}
                                                </td>
                                                <td className="py-3 pr-4 text-muted-foreground">{row.client}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline">{row.status_label}</Badge>
                                                </td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{format(row.revenue)}</td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{format(row.cost)}</td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{format(row.margin)}</td>
                                                <td className={`py-3 text-right tabular-nums font-medium ${marginColor(row.margin_pct)}`}>
                                                    {row.margin_pct}%
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t font-semibold">
                                            <td className="pt-3" colSpan={3}>Totals</td>
                                            <td className="pt-3 text-right tabular-nums">{format(report.totals.revenue)}</td>
                                            <td className="pt-3 text-right tabular-nums">{format(report.totals.cost)}</td>
                                            <td className="pt-3 text-right tabular-nums">{format(report.totals.margin)}</td>
                                            <td className={`pt-3 text-right tabular-nums ${marginColor(report.totals.margin_pct)}`}>
                                                {report.totals.margin_pct}%
                                            </td>
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

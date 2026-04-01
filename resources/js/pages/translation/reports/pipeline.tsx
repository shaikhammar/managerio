import { Head, Link } from '@inertiajs/react';
import React from 'react';
import { AlertTriangle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Reports', href: '/translation/reports' },
    { title: 'Pipeline', href: '/translation/reports/pipeline' },
];

type PipelineRow = {
    id: number;
    name: string;
    reference: string | null;
    client: string;
    status: string;
    status_label: string;
    deadline: string | null;
    days_until_deadline: number | null;
    expected_revenue: number;
    language_pairs: string[];
};

type Report = {
    rows: PipelineRow[];
    totals: { project_count: number; expected_revenue: number };
};

type Props = {
    report: Report;
};

function deadlineBadge(days: number | null): React.ReactElement | null {
    if (days === null) {
return null;
}

    if (days < 0) {
return <span className="text-xs text-red-600 dark:text-red-400 flex items-center gap-1"><AlertTriangle className="size-3" />{Math.abs(days)}d overdue</span>;
}

    if (days === 0) {
return <span className="text-xs text-amber-600 dark:text-amber-400 font-medium">Due today</span>;
}

    if (days <= 3) {
return <span className="text-xs text-amber-600 dark:text-amber-400">{days}d left</span>;
}

    return <span className="text-xs text-muted-foreground">{days}d left</span>;
}

export default function Pipeline({ report }: Props) {
    const { format } = useCurrency();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pipeline Report" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Pipeline Report</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Active projects with expected invoice value — current snapshot
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Active Projects</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{report.totals.project_count}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Pipeline Value</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{format(report.totals.expected_revenue)}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardContent className="pt-4">
                        {report.rows.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">No active projects in the pipeline.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-3 font-medium text-muted-foreground">Project</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Client</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Status</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Languages</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Deadline</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Expected Value</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {report.rows.map((row) => (
                                            <tr key={row.id} className="hover:bg-muted/40 transition-colors">
                                                <td className="py-3 pr-4">
                                                    <Link
                                                        href={`/translation/projects/${row.id}`}
                                                        className="font-medium hover:text-primary transition-colors"
                                                    >
                                                        {row.name}
                                                    </Link>
                                                    {row.reference && <div className="text-xs text-muted-foreground">{row.reference}</div>}
                                                </td>
                                                <td className="py-3 pr-4 text-muted-foreground">{row.client}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline">{row.status_label}</Badge>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {row.language_pairs.map((lp) => (
                                                            <span key={lp} className="text-xs bg-muted px-1.5 py-0.5 rounded">{lp}</span>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-4 text-right">
                                                    <div className="tabular-nums">{row.deadline ?? '—'}</div>
                                                    <div>{deadlineBadge(row.days_until_deadline)}</div>
                                                </td>
                                                <td className="py-3 text-right tabular-nums font-medium">{format(row.expected_revenue)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t font-semibold">
                                            <td className="pt-3" colSpan={5}>Total Pipeline</td>
                                            <td className="pt-3 text-right tabular-nums">{format(report.totals.expected_revenue)}</td>
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

import { Head, router } from '@inertiajs/react';
import { CheckCircle, XCircle, AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Reports', href: '/translation/reports' },
    { title: 'Delivery Performance', href: '/translation/reports/delivery-performance' },
];

type CompletedRow = {
    id: number;
    name: string;
    client: string;
    status: string;
    status_label: string;
    deadline: string;
    on_time: boolean;
};

type OverdueRow = {
    id: number;
    name: string;
    client: string;
    status: string;
    status_label: string;
    deadline: string;
    days_overdue: number;
};

type Report = {
    period: { start: string; end: string };
    summary: { total: number; on_time: number; late: number; on_time_rate: number };
    completed: CompletedRow[];
    overdue: OverdueRow[];
};

type Props = {
    report: Report;
    filters: { start_date: string; end_date: string };
};

export default function DeliveryPerformance({ report, filters }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/reports/delivery-performance', { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    const rateColor =
        report.summary.on_time_rate >= 90
            ? 'text-emerald-600 dark:text-emerald-400'
            : report.summary.on_time_rate >= 70
              ? 'text-amber-600 dark:text-amber-400'
              : 'text-red-600 dark:text-red-400';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Delivery Performance" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Delivery Performance</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        On-time delivery rate for completed projects and currently overdue active projects
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

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">On-Time Rate</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className={`text-2xl font-bold ${rateColor}`}>{report.summary.on_time_rate}%</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Delivered</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{report.summary.total}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">On Time</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{report.summary.on_time}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Late</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold text-red-600 dark:text-red-400">{report.summary.late}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Overdue active projects */}
                {report.overdue.length > 0 && (
                    <Card className="border-amber-300 dark:border-amber-700">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                <AlertTriangle className="size-4" />
                                Currently Overdue ({report.overdue.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-3 font-medium text-muted-foreground">Project</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Client</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Status</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Deadline</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Days Overdue</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {report.overdue.map((row) => (
                                            <tr key={row.id} className="hover:bg-muted/40 transition-colors">
                                                <td className="py-3 pr-4 font-medium">{row.name}</td>
                                                <td className="py-3 pr-4 text-muted-foreground">{row.client}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline">{row.status_label}</Badge>
                                                </td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{row.deadline}</td>
                                                <td className="py-3 text-right tabular-nums text-red-600 dark:text-red-400 font-medium">
                                                    {row.days_overdue}d
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Completed projects in period */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Delivered Projects in Period</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {report.completed.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">No completed projects with deadlines in this period.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pb-3 font-medium text-muted-foreground">Project</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Client</th>
                                            <th className="pb-3 font-medium text-muted-foreground">Status</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Deadline</th>
                                            <th className="pb-3 font-medium text-muted-foreground text-right">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {report.completed.map((row) => (
                                            <tr key={row.id} className="hover:bg-muted/40 transition-colors">
                                                <td className="py-3 pr-4 font-medium">{row.name}</td>
                                                <td className="py-3 pr-4 text-muted-foreground">{row.client}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline">{row.status_label}</Badge>
                                                </td>
                                                <td className="py-3 pr-4 text-right tabular-nums">{row.deadline}</td>
                                                <td className="py-3 text-right">
                                                    {row.on_time ? (
                                                        <CheckCircle className="size-4 text-emerald-500 ml-auto" />
                                                    ) : (
                                                        <XCircle className="size-4 text-red-500 ml-auto" />
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

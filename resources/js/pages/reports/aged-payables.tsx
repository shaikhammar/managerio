import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Printer, Calendar } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { useState } from 'react';
import React from 'react';

type AgedItem = {
    invoice_id: number;
    number: string;
    contact: string;
    date: string;
    due_date: string;
    amount: number;
};

type Bucket = {
    label: string;
    min: number;
    max: number;
    total: number;
    items: AgedItem[];
};

type Props = {
    report: Record<string, Bucket>;
    filters: { as_of_date: string };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
    { title: 'Aged Payables', href: '/reports/aged-payables' },
];

export default function AgedPayables({ report, filters }: Props) {
    const [asOfDate, setAsOfDate] = useState(filters.as_of_date);

    const grandTotal = Object.values(report).reduce((sum, b) => sum + b.total, 0);

    function handleFilter() {
        router.get('/reports/aged-payables', { as_of_date: asOfDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aged Payables Report" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/reports">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Aged Payables</h1>
                            <p className="text-muted-foreground text-sm">Unpaid purchase invoices grouped by age</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={() => window.print()}>
                            <Printer className="mr-2 size-4" /> Print
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-col sm:flex-row items-end gap-4">
                            <div className="space-y-2 flex-1">
                                <label className="text-xs font-medium uppercase text-muted-foreground">As of Date</label>
                                <div className="relative">
                                    <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                                    <Input 
                                        type="date" 
                                        className="pl-9" 
                                        value={asOfDate} 
                                        onChange={(e) => setAsOfDate(e.target.value)} 
                                    />
                                </div>
                            </div>
                            <Button onClick={handleFilter}>Update Report</Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {Object.entries(report).map(([key, bucket]) => (
                        <Card key={key}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    {bucket.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xl font-bold">{formatCurrency(bucket.total)}</p>
                                <p className="text-[10px] text-muted-foreground mt-1">
                                    {bucket.items.length} invoice(s)
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Supplier / Invoice</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Due Date</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {grandTotal === 0 ? (
                                <tr>
                                    <td colSpan={4} className="py-12 text-center text-muted-foreground">
                                        No outstanding payables found
                                    </td>
                                </tr>
                            ) : (
                                Object.entries(report).map(([key, bucket]) => (
                                    bucket.items.length > 0 && (
                                        <React.Fragment key={key}>
                                            <tr className="bg-muted/20">
                                                <td colSpan={4} className="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-muted-foreground">
                                                    {bucket.label}
                                                </td>
                                            </tr>
                                            {bucket.items.map((item) => (
                                                <tr key={item.invoice_id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                                    <td className="py-3 px-4 text-sm">
                                                        <p className="font-medium">{item.contact}</p>
                                                        <Link 
                                                            href={`/purchases/invoices/${item.invoice_id}`}
                                                            className="text-[10px] text-blue-600 hover:underline font-mono"
                                                        >
                                                            #{item.number}
                                                        </Link>
                                                    </td>
                                                    <td className="py-3 px-4 text-sm text-muted-foreground">{item.date}</td>
                                                    <td className="py-3 px-4 text-sm text-muted-foreground">{item.due_date}</td>
                                                    <td className="py-3 px-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                                        ({formatCurrency(item.amount)})
                                                    </td>
                                                </tr>
                                            ))}
                                        </React.Fragment>
                                    )
                                ))
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="bg-muted/30 font-bold border-t-2">
                                <td colSpan={3} className="py-3 px-4 text-sm text-right uppercase tracking-wider">Total Outstanding</td>
                                <td className="py-3 px-4 text-right text-sm text-red-600 dark:text-red-400">({formatCurrency(grandTotal)})</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

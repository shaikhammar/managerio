import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { useEffect, useState } from 'react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import ReportController from '@/actions/App/Http/Controllers/Reports/ReportController';
import type { BreadcrumbItem } from '@/types';

type LedgerTransaction = {
    id: number;
    debit: number;
    credit: number;
    description: string | null;
    journalEntry: { id: number; date: string; description: string; reference: string | null };
    contact: { name: string } | null;
};

type LedgerAccount = {
    account: { id: number; code: string; name: string; type: string };
    transactions: LedgerTransaction[];
    closing_balance: number;
};

type Props = {
    ledger: LedgerAccount[];
    filters: { start_date: string; end_date: string };
    asyncStatus?: 'queued' | 'processing' | 'completed' | 'failed';
    cacheKey?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: ReportController.index.url() },
    { title: 'General Ledger', href: ReportController.generalLedger.url() },
];

export default function GeneralLedger({ ledger, filters, asyncStatus, cacheKey }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);
    const [status, setStatus] = useState(asyncStatus ?? 'completed');
    const [currentLedger, setCurrentLedger] = useState<LedgerAccount[]>(ledger);

    useEffect(() => {
        if (status === 'completed' || status === 'failed' || !cacheKey) {
            return;
        }

        const interval = setInterval(async () => {
            try {
                const response = await fetch(`${ReportController.status.url()}?key=${encodeURIComponent(cacheKey)}`);
                const data = await response.json();

                if (data.status === 'completed') {
                    setCurrentLedger(data.data ?? []);
                    setStatus('completed');
                    clearInterval(interval);
                } else if (data.status === 'failed') {
                    setStatus('failed');
                    clearInterval(interval);
                }
            } catch {
                // network error — keep polling
            }
        }, 2000);

        return () => clearInterval(interval);
    }, [status, cacheKey]);

    function handleFilter(e: React.FormEvent) {
        e.preventDefault();
        router.get(ReportController.generalLedger.url(), { start_date: startDate, end_date: endDate }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="General Ledger" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={ReportController.index.url()}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">General Ledger</h1>
                            <p className="text-muted-foreground text-sm">
                                {filters.start_date} to {filters.end_date}
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => window.print()}>
                        <Printer className="mr-2 size-4" /> Print
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleFilter} className="flex flex-col sm:flex-row items-end gap-4">
                            <div className="space-y-1 flex-1">
                                <Label htmlFor="start_date" className="text-xs uppercase text-muted-foreground">From</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>
                            <div className="space-y-1 flex-1">
                                <Label htmlFor="end_date" className="text-xs uppercase text-muted-foreground">To</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>
                            <Button type="submit">Update Report</Button>
                        </form>
                    </CardContent>
                </Card>

                {status === 'failed' && (
                    <div className="rounded-lg border border-destructive/40 bg-destructive/10 py-10 text-center text-destructive">
                        Report generation failed. Please try again.
                    </div>
                )}

                {(status === 'queued' || status === 'processing') && (
                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground text-center animate-pulse">
                            Generating report… this may take a moment.
                        </p>
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="rounded-lg border overflow-hidden">
                                <div className="bg-muted/50 px-4 py-3 h-11 animate-pulse" />
                                <div className="p-4 space-y-2">
                                    {[1, 2, 3, 4].map((j) => (
                                        <div key={j} className="h-8 bg-muted/40 rounded animate-pulse" />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {status === 'completed' && currentLedger.length === 0 && (
                    <div className="rounded-lg border py-16 text-center text-muted-foreground">
                        No transactions found for this period.
                    </div>
                )}

                {status === 'completed' && currentLedger.length > 0 && (
                    <div className="space-y-6">
                        {currentLedger.map((item) => {
                            const totalDebit = item.transactions.reduce((s, t) => s + t.debit, 0);
                            const totalCredit = item.transactions.reduce((s, t) => s + t.credit, 0);

                            return (
                                <div key={item.account.id} className="rounded-lg border overflow-hidden">
                                    <div className="bg-muted/50 px-4 py-3 flex items-center justify-between">
                                        <div>
                                            <span className="font-mono text-sm text-muted-foreground mr-2">{item.account.code}</span>
                                            <span className="font-semibold">{item.account.name}</span>
                                        </div>
                                        <span className={`text-sm font-medium ${item.closing_balance >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                            Balance: {formatCurrency(Math.abs(item.closing_balance))}
                                            {item.closing_balance < 0 ? ' CR' : ' DR'}
                                        </span>
                                    </div>
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/20 text-muted-foreground text-xs uppercase tracking-wider">
                                                <th className="text-left py-2 px-4">Date</th>
                                                <th className="text-left py-2 px-4">Description</th>
                                                <th className="text-left py-2 px-4">Reference</th>
                                                <th className="text-right py-2 px-4">Debit</th>
                                                <th className="text-right py-2 px-4">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {item.transactions.map((tx) => (
                                                <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/20 transition-colors">
                                                    <td className="py-2 px-4 text-muted-foreground whitespace-nowrap">
                                                        {tx.journalEntry.date}
                                                    </td>
                                                    <td className="py-2 px-4">
                                                        <p>{tx.description || tx.journalEntry.description}</p>
                                                        {tx.contact && (
                                                            <p className="text-xs text-muted-foreground">{tx.contact.name}</p>
                                                        )}
                                                    </td>
                                                    <td className="py-2 px-4 font-mono text-xs text-muted-foreground">
                                                        {tx.journalEntry.reference || '—'}
                                                    </td>
                                                    <td className="py-2 px-4 text-right font-medium">
                                                        {tx.debit > 0 ? formatCurrency(tx.debit) : ''}
                                                    </td>
                                                    <td className="py-2 px-4 text-right font-medium">
                                                        {tx.credit > 0 ? formatCurrency(tx.credit) : ''}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot>
                                            <tr className="border-t-2 font-semibold bg-muted/10">
                                                <td colSpan={3} className="py-2 px-4 text-right text-xs uppercase text-muted-foreground">Totals</td>
                                                <td className="py-2 px-4 text-right">{formatCurrency(totalDebit)}</td>
                                                <td className="py-2 px-4 text-right">{formatCurrency(totalCredit)}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

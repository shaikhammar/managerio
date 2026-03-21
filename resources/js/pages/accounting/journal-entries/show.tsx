import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, History, RotateCcw, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { JournalEntry, BreadcrumbItem } from '@/types';

type Props = {
    entry: JournalEntry;
};

export default function JournalEntryShow({ entry }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Journal Entries', href: '/accounting/journal-entries' },
        { title: entry.entry_number, href: `/accounting/journal-entries/${entry.id}` },
    ];

    const totalDebit = entry.lines?.reduce((sum, line) => sum + parseFloat(line.debit.toString()), 0) || 0;
    const totalCredit = entry.lines?.reduce((sum, line) => sum + parseFloat(line.credit.toString()), 0) || 0;

    function handlePost() {
        router.post(`/accounting/journal-entries/${entry.id}/post`);
    }

    function handleReverse() {
        const reason = prompt('Please enter a reason for reversal:');

        if (reason) {
            router.post(`/accounting/journal-entries/${entry.id}/reverse`, { reason });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Journal Entry ${entry.entry_number}`} />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/accounting/journal-entries">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono tracking-tight">{entry.entry_number}</h1>
                                {entry.is_posted ? (
                                    <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        Posted
                                    </span>
                                ) : (
                                    <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        Draft
                                    </span>
                                )}
                            </div>
                            <p className="text-muted-foreground text-sm">{entry.date}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {!entry.is_posted && (
                            <Button onClick={handlePost}>
                                <CheckCircle2 className="mr-2 size-4" /> Post Entry
                            </Button>
                        )}
                        {entry.is_posted && !entry.reversal_of_id && (
                            <Button variant="outline" onClick={handleReverse}>
                                <RotateCcw className="mr-2 size-4" /> Reverse
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    <Card className="md:col-span-2">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">{entry.description}</p>
                            {entry.reference && (
                                <p className="text-xs text-muted-foreground mt-2">
                                    <span className="font-semibold uppercase tracking-wider text-[10px]">Ref:</span> {entry.reference}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Metadata</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex items-center gap-2 text-xs">
                                <User className="size-3 text-muted-foreground" />
                                <span className="text-muted-foreground">Created by:</span>
                                <span className="font-medium">{entry.creator?.name || 'System'}</span>
                            </div>
                            {entry.posted_at && (
                                <div className="flex items-center gap-2 text-xs">
                                    <CheckCircle2 className="size-3 text-muted-foreground" />
                                    <span className="text-muted-foreground">Posted at:</span>
                                    <span className="font-medium">{entry.posted_at}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {entry.reversal_of_id && (
                    <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-100 dark:border-blue-800 flex items-center gap-3">
                        <History className="size-5 text-blue-600" />
                        <div className="text-sm">
                            <p className="text-blue-800 dark:text-blue-300 font-medium">This is a reversal entry</p>
                            <p className="text-blue-600 dark:text-blue-400">
                                Reversing entry{' '}
                                <Link 
                                    href={`/accounting/journal-entries/${entry.reversal_of_id}`}
                                    className="font-mono underline decoration-dotted"
                                >
                                    #{entry.reversal_of?.entry_number}
                                </Link>
                            </p>
                        </div>
                    </div>
                )}

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Account</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Debit</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {entry.lines?.map((line) => (
                                <tr key={line.id} className="border-b last:border-0">
                                    <td className="py-3 px-4 text-sm">
                                        <div className="flex flex-col">
                                            <span className="font-medium">{line.account?.name}</span>
                                            <span className="font-mono text-[10px] text-muted-foreground">{line.account?.code}</span>
                                        </div>
                                    </td>
                                    <td className="py-3 px-4 text-sm text-muted-foreground italic">
                                        {line.description || '—'}
                                    </td>
                                    <td className="py-3 px-4 text-right text-sm font-medium">
                                        {line.debit > 0 ? formatCurrency(line.debit) : '—'}
                                    </td>
                                    <td className="py-3 px-4 text-right text-sm font-medium">
                                        {line.credit > 0 ? formatCurrency(line.credit) : '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="bg-muted/30 font-bold border-t-2">
                                <td colSpan={2} className="py-3 px-4 text-sm text-right uppercase tracking-wider">Total</td>
                                <td className="py-3 px-4 text-right text-sm">{formatCurrency(totalDebit)}</td>
                                <td className="py-3 px-4 text-right text-sm">{formatCurrency(totalCredit)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

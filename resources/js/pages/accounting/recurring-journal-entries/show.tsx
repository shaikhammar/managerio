import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, Pause, Play, Trash2, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { Account, BreadcrumbItem, RecurringJournalEntry } from '@/types';

type Props = {
    entry: RecurringJournalEntry;
    accounts: Record<number, Pick<Account, 'id' | 'code' | 'name'>>;
};

export default function RecurringJournalEntryShow({ entry, accounts }: Props) {
    const { format } = useCurrency();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Recurring Journal Entries', href: '/accounting/recurring-journal-entries' },
        { title: entry.name, href: `/accounting/recurring-journal-entries/${entry.id}` },
    ];

    function handleToggle() {
        router.post(`/accounting/recurring-journal-entries/${entry.id}/toggle-active`);
    }

    function handleDelete() {
        if (confirm('Delete this recurring entry? Posted journal entries will remain.')) {
            router.delete(`/accounting/recurring-journal-entries/${entry.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={entry.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/accounting/recurring-journal-entries">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold tracking-tight">{entry.name}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider ${
                                    entry.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                }`}>
                                    {entry.is_active ? 'Active' : 'Paused'}
                                </span>
                            </div>
                            {entry.description && (
                                <p className="text-muted-foreground text-sm">{entry.description}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleToggle}>
                            {entry.is_active ? (
                                <><Pause className="mr-2 size-4" /> Pause</>
                            ) : (
                                <><Play className="mr-2 size-4" /> Activate</>
                            )}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/accounting/recurring-journal-entries/${entry.id}/edit`}>
                                <Edit className="mr-2 size-4" /> Edit
                            </Link>
                        </Button>
                        <Button variant="outline" onClick={handleDelete} className="text-destructive hover:text-destructive">
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">Schedule</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Frequency</span>
                                <Badge variant="secondary" className="capitalize">{entry.frequency}</Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Day of Month</span>
                                <span className="font-medium">{entry.day_of_month}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Start Date</span>
                                <span className="font-medium">{entry.start_date}</span>
                            </div>
                            {entry.end_date && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">End Date</span>
                                    <span className="font-medium">{entry.end_date}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">Run History</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Next Run</span>
                                <span className="font-medium">{entry.next_run_date}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Last Run</span>
                                <span className="font-medium">{entry.last_run_at ? entry.last_run_at.slice(0, 10) : '—'}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">Created By</CardTitle></CardHeader>
                        <CardContent className="flex items-center gap-2 text-sm">
                            <User className="size-3.5 text-muted-foreground" />
                            <span>{entry.creator?.name || 'System'}</span>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>Template Lines</CardTitle></CardHeader>
                    <CardContent className="p-0">
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
                                {entry.template_lines.map((line, idx) => {
                                    const account = accounts[line.account_id];
                                    return (
                                        <tr key={idx} className="border-b last:border-0">
                                            <td className="py-3 px-4 text-sm">
                                                {account ? (
                                                    <div className="flex flex-col">
                                                        <span className="font-medium">{account.name}</span>
                                                        <span className="font-mono text-[10px] text-muted-foreground">{account.code}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">Account #{line.account_id}</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-sm text-muted-foreground italic">
                                                {line.description || '—'}
                                            </td>
                                            <td className="py-3 px-4 text-right text-sm font-medium">
                                                {line.debit > 0 ? format(line.debit) : '—'}
                                            </td>
                                            <td className="py-3 px-4 text-right text-sm font-medium">
                                                {line.credit > 0 ? format(line.credit) : '—'}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

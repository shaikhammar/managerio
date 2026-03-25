import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, RefreshCw, Pause, Play } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, RecurringJournalEntry } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recurring Journal Entries', href: '/accounting/recurring-journal-entries' },
];

type FrequencyOption = { value: string; label: string };

type Props = {
    entries: PaginatedData<RecurringJournalEntry>;
    filters: { search?: string; frequency?: string };
    frequencies: FrequencyOption[];
};

export default function RecurringJournalEntryIndex({ entries, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/accounting/recurring-journal-entries', { search }, { preserveState: true });
    }

    function toggleActive(entry: RecurringJournalEntry) {
        router.post(`/accounting/recurring-journal-entries/${entry.id}/toggle-active`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recurring Journal Entries" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Recurring Journal Entries</h1>
                        <p className="text-muted-foreground text-sm">Scheduled journal entries that auto-post on a regular interval</p>
                    </div>
                    <Button asChild>
                        <Link href="/accounting/recurring-journal-entries/create">
                            <Plus className="mr-2 size-4" /> New Recurring Entry
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input
                            placeholder="Search entries..."
                            className="pl-9"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </form>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Name</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Frequency</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Next Run</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Last Run</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="w-[80px]"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-12 text-center">
                                        <RefreshCw className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No recurring entries yet</p>
                                    </td>
                                </tr>
                            ) : (
                                entries.data.map((entry) => (
                                    <tr key={entry.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link
                                                href={`/accounting/recurring-journal-entries/${entry.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {entry.name}
                                            </Link>
                                            {entry.description && (
                                                <p className="text-xs text-muted-foreground mt-0.5">{entry.description}</p>
                                            )}
                                        </td>
                                        <td className="py-3 px-4">
                                            <Badge variant="secondary" className="capitalize">{entry.frequency}</Badge>
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{entry.next_run_date}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">
                                            {entry.last_run_at ? entry.last_run_at.slice(0, 10) : '—'}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                entry.is_active
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                            }`}>
                                                {entry.is_active ? 'Active' : 'Paused'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                onClick={() => toggleActive(entry)}
                                                title={entry.is_active ? 'Pause' : 'Activate'}
                                            >
                                                {entry.is_active ? <Pause className="size-4" /> : <Play className="size-4" />}
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {entries.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {entries.from} to {entries.to} of {entries.total}
                        </p>
                        <div className="flex gap-2">
                            {entries.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={entries.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {entries.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={entries.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

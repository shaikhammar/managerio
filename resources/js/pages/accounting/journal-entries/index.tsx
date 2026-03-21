import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, BookOpen } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, JournalEntry, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal Entries', href: '/accounting/journal-entries' },
];

type Props = {
    journalEntries: PaginatedData<JournalEntry>;
    filters: { search?: string };
};

export default function JournalEntryIndex({ journalEntries, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/accounting/journal-entries', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Journal Entries" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Journal Entries</h1>
                        <p className="text-muted-foreground text-sm">Manual and system-generated journal entries</p>
                    </div>
                    <Button asChild>
                        <Link href="/accounting/journal-entries/create"><Plus className="mr-2 size-4" /> New Entry</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input placeholder="Search entries..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                </form>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Entry #</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Source</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {journalEntries.data.length === 0 ? (
                                <tr><td colSpan={5} className="py-12 text-center">
                                    <BookOpen className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                    <p className="text-muted-foreground">No journal entries yet</p>
                                </td></tr>
                            ) : (
                                journalEntries.data.map((entry) => (
                                    <tr key={entry.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={`/accounting/journal-entries/${entry.id}`} className="font-mono text-sm font-medium hover:underline">
                                                {entry.entry_number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{entry.date}</td>
                                        <td className="py-3 px-4 text-sm">{entry.description || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">
                                            {entry.source_type ? (
                                                <Badge variant="secondary" className="text-xs capitalize">{entry.source_type.replace('App\\Models\\', '')}</Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-xs">Manual</Badge>
                                            )}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                entry.is_posted
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                            }`}>
                                                {entry.is_posted ? 'Posted' : 'Draft'}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {journalEntries.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">Showing {journalEntries.from} to {journalEntries.to} of {journalEntries.total}</p>
                        <div className="flex gap-2">
                            {journalEntries.links.prev && <Button variant="outline" size="sm" asChild><Link href={journalEntries.links.prev}>Previous</Link></Button>}
                            {journalEntries.links.next && <Button variant="outline" size="sm" asChild><Link href={journalEntries.links.next}>Next</Link></Button>}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

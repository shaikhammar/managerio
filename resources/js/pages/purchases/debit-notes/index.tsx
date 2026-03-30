import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, FileText } from 'lucide-react';
import { useState } from 'react';
import DebitNoteController from '@/actions/App/Http/Controllers/Purchases/DebitNoteController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Debit Notes', href: DebitNoteController.index.url() },
];

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    void: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

type Props = {
    debitNotes: PaginatedData<Invoice>;
    filters: { search?: string; status?: string };
};

export default function DebitNoteIndex({ debitNotes, filters }: Props) {
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(DebitNoteController.index.url(), { search, status: filters.status }, { preserveState: true });
    }

    function handleStatusChange(status: string) {
        router.get(DebitNoteController.index.url(), { search: filters.search, status: status === 'all' ? undefined : status }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Debit Notes" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Debit Notes</h1>
                        <p className="text-muted-foreground text-sm">Return goods or services to suppliers</p>
                    </div>
                    <Button asChild>
                        <Link href={DebitNoteController.create.url()}>
                            <Plus className="mr-2 size-4" />
                            New Debit Note
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input placeholder="Search debit notes..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                        </div>
                    </form>
                    <Select value={filters.status || 'all'} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="sent">Posted</SelectItem>
                            <SelectItem value="void">Void</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Number</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Supplier</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Total Debit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {debitNotes.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No debit notes found</p>
                                    </td>
                                </tr>
                            ) : (
                                debitNotes.data.map((note) => (
                                    <tr key={note.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={DebitNoteController.show.url(note.id)} className="font-mono text-sm font-medium hover:underline">
                                                {note.number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{note.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{note.date}</td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[note.status] || ''}`}>
                                                {note.status === 'sent' ? 'Posted' : note.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right font-medium text-sm text-amber-600 dark:text-amber-400">
                                            {format(note.total)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {debitNotes.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {debitNotes.from} to {debitNotes.to} of {debitNotes.total}
                        </p>
                        <div className="flex gap-2">
                            {debitNotes.links.prev && (<Button variant="outline" size="sm" asChild><Link href={debitNotes.links.prev}>Previous</Link></Button>)}
                            {debitNotes.links.next && (<Button variant="outline" size="sm" asChild><Link href={debitNotes.links.next}>Next</Link></Button>)}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

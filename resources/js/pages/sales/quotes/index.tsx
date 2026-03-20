import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice, PaginatedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quotes', href: '/sales/quotes' },
];

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(amount);
}

type Props = {
    quotes: PaginatedData<Invoice>;
    filters: { search?: string; status?: string };
};

export default function QuoteIndex({ quotes, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/sales/quotes', { search, status: filters.status }, { preserveState: true });
    }

    function handleStatusChange(status: string) {
        router.get('/sales/quotes', { search: filters.search, status: status === 'all' ? undefined : status }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sales Quotes" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Quotes</h1>
                        <p className="text-muted-foreground text-sm">Create and manage sales quotes (estimates)</p>
                    </div>
                    <Button asChild>
                        <Link href="/sales/quotes/create">
                            <Plus className="mr-2 size-4" />
                            New Quote
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input placeholder="Search quotes..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                        </div>
                    </form>
                    <Select value={filters.status || 'all'} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="approved">Approved / Converted</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Number</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Customer</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Expiry</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {quotes.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No quotes found</p>
                                    </td>
                                </tr>
                            ) : (
                                quotes.data.map((quote) => (
                                    <tr key={quote.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={`/sales/quotes/${quote.id}`} className="font-mono text-sm font-medium hover:underline">
                                                {quote.number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{quote.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{quote.date}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{quote.due_date || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[quote.status] || ''}`}>
                                                {quote.status === 'approved' ? 'Converted' : quote.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right font-medium text-sm">{formatCurrency(quote.total)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {quotes.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {quotes.from} to {quotes.to} of {quotes.total}
                        </p>
                        <div className="flex gap-2">
                            {quotes.links.prev && (<Button variant="outline" size="sm" asChild><Link href={quotes.links.prev}>Previous</Link></Button>)}
                            {quotes.links.next && (<Button variant="outline" size="sm" asChild><Link href={quotes.links.next}>Next</Link></Button>)}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

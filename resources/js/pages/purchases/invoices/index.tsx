import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, FileText } from 'lucide-react';
import { useState } from 'react';
import PurchaseInvoiceController from '@/actions/App/Http/Controllers/Purchases/PurchaseInvoiceController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Invoices', href: PurchaseInvoiceController.index.url() },
];

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    partially_paid: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    overdue: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    void: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

type Props = {
    invoices: PaginatedData<Invoice>;
    filters: { search?: string; status?: string };
};

export default function PurchaseInvoiceIndex({ invoices, filters }: Props) {
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(PurchaseInvoiceController.index.url(), { search, status: filters.status }, { preserveState: true });
    }

    function handleStatusChange(status: string) {
        router.get(PurchaseInvoiceController.index.url(), { search: filters.search, status: status === 'all' ? undefined : status }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Purchase Invoices" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Purchase Invoices</h1>
                        <p className="text-muted-foreground text-sm">Manage bills and purchase invoices</p>
                    </div>
                    <Button asChild>
                        <Link href={PurchaseInvoiceController.create.url()}>
                            <Plus className="mr-2 size-4" />
                            New Purchase Invoice
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input placeholder="Search invoices..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                        </div>
                    </form>
                    <Select value={filters.status || 'all'} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="sent">Sent</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="partially_paid">Partially Paid</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
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
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Due Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Total</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No purchase invoices found</p>
                                    </td>
                                </tr>
                            ) : (
                                invoices.data.map((invoice) => (
                                    <tr key={invoice.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={PurchaseInvoiceController.show.url(invoice)} className="font-mono text-sm font-medium hover:underline">
                                                {invoice.number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{invoice.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{invoice.date}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{invoice.due_date || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[invoice.status] || ''}`}>
                                                {invoice.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right font-medium text-sm">{format(invoice.total)}</td>
                                        <td className="py-3 px-4 text-right text-sm">
                                            <span className={invoice.balance_due > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-muted-foreground'}>
                                                {format(invoice.balance_due)}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {invoices.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {invoices.from} to {invoices.to} of {invoices.total}
                        </p>
                        <div className="flex gap-2">
                            {invoices.links.prev && (<Button variant="outline" size="sm" asChild><Link href={invoices.links.prev}>Previous</Link></Button>)}
                            {invoices.links.next && (<Button variant="outline" size="sm" asChild><Link href={invoices.links.next}>Next</Link></Button>)}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

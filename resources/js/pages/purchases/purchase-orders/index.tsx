import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, FileText } from 'lucide-react';
import { useState } from 'react';
import PurchaseOrderController from '@/actions/App/Http/Controllers/Purchases/PurchaseOrderController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Orders', href: PurchaseOrderController.index.url() },
];

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    accepted: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    in_progress: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
    delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    partially_received: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    received: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    invoiced: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
};

type Props = {
    purchaseOrders: PaginatedData<Invoice>;
    filters: { search?: string; status?: string };
};

export default function PurchaseOrderIndex({ purchaseOrders, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(PurchaseOrderController.index.url(), { search, status: filters.status }, { preserveState: true });
    }

    function handleStatusChange(status: string) {
        router.get(PurchaseOrderController.index.url(), { search: filters.search, status: status === 'all' ? undefined : status }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Purchase Orders" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Purchase Orders</h1>
                        <p className="text-muted-foreground text-sm">Manage orders sent to suppliers</p>
                    </div>
                    <Button asChild>
                        <Link href={PurchaseOrderController.create.url()}>
                            <Plus className="mr-2 size-4" />
                            New Purchase Order
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input placeholder="Search purchase orders..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
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
                            <SelectItem value="accepted">Accepted</SelectItem>
                            <SelectItem value="in_progress">In Progress</SelectItem>
                            <SelectItem value="delivered">Delivered</SelectItem>
                            <SelectItem value="partially_received">Partially Received</SelectItem>
                            <SelectItem value="received">Received</SelectItem>
                            <SelectItem value="invoiced">Invoiced</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
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
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Expected</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchaseOrders.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No purchase orders found</p>
                                    </td>
                                </tr>
                            ) : (
                                purchaseOrders.data.map((po) => (
                                    <tr key={po.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={PurchaseOrderController.show.url(po.id)} className="font-mono text-sm font-medium hover:underline">
                                                {po.number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{po.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{po.date}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{po.due_date || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[po.status] || ''}`}>
                                                {po.status.replace(/_/g, ' ')}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right font-medium text-sm">
                                            {formatCurrency(po.total, po.currency_code)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {purchaseOrders.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {purchaseOrders.from} to {purchaseOrders.to} of {purchaseOrders.total}
                        </p>
                        <div className="flex gap-2">
                            {purchaseOrders.links.prev && (<Button variant="outline" size="sm" asChild><Link href={purchaseOrders.links.prev}>Previous</Link></Button>)}
                            {purchaseOrders.links.next && (<Button variant="outline" size="sm" asChild><Link href={purchaseOrders.links.next}>Next</Link></Button>)}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

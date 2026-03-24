import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, CreditCard } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BreadcrumbItem, Payment, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Receipts', href: '/payments/receipts' },
];

type Props = {
    receipts: PaginatedData<Payment>;
    filters: { search?: string };
};

export default function ReceiptIndex({ receipts, filters }: Props) {
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/payments/receipts', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Receipts" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Receipts</h1>
                        <p className="text-muted-foreground text-sm">Customer payments received</p>
                    </div>
                    <Button asChild>
                        <Link href="/payments/receipts/create"><Plus className="mr-2 size-4" /> Receive Payment</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input placeholder="Search receipts..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                </form>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Number</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Customer</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Reference</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {receipts.data.length === 0 ? (
                                <tr><td colSpan={5} className="py-12 text-center">
                                    <CreditCard className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                    <p className="text-muted-foreground">No receipts recorded yet</p>
                                </td></tr>
                            ) : (
                                receipts.data.map((receipt) => (
                                    <tr key={receipt.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors cursor-pointer" onClick={() => router.visit(`/payments/receipts/${receipt.id}`)}>
                                        <td className="py-3 px-4 font-mono text-sm font-medium">
                                            <Link href={`/payments/receipts/${receipt.id}`} className="hover:underline" onClick={(e) => e.stopPropagation()}>{receipt.number}</Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{receipt.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{receipt.date}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{receipt.reference || '—'}</td>
                                        <td className="py-3 px-4 text-right text-sm font-medium text-emerald-600 dark:text-emerald-400">+{format(receipt.amount)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {receipts.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">Showing {receipts.from} to {receipts.to} of {receipts.total}</p>
                        <div className="flex gap-2">
                            {receipts.links.prev && <Button variant="outline" size="sm" asChild><Link href={receipts.links.prev}>Previous</Link></Button>}
                            {receipts.links.next && <Button variant="outline" size="sm" asChild><Link href={receipts.links.next}>Next</Link></Button>}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

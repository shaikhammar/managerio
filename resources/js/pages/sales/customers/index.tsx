import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Contact, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: '/sales/customers' },
];

type Props = {
    customers: PaginatedData<Contact>;
    filters: { search?: string };
};

export default function CustomerIndex({ customers, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/sales/customers', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Customers</h1>
                        <p className="text-muted-foreground text-sm">Manage your customers</p>
                    </div>
                    <Button asChild>
                        <Link href="/sales/customers/create">
                            <Plus className="mr-2 size-4" />
                            Add Customer
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input
                            placeholder="Search customers..."
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
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Email</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Phone</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center text-muted-foreground">
                                        No customers found. Create your first customer to get started.
                                    </td>
                                </tr>
                            ) : (
                                customers.data.map((customer) => (
                                    <tr key={customer.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link href={`/sales/customers/${customer.id}`} className="font-medium hover:underline">
                                                {customer.name}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{customer.email || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{customer.phone || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${customer.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                {customer.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/sales/customers/${customer.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {customers.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {customers.from} to {customers.to} of {customers.total}
                        </p>
                        <div className="flex gap-2">
                            {customers.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={customers.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {customers.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={customers.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

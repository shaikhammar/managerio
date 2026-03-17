import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Contact, PaginatedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers', href: '/purchases/suppliers' },
];

type Props = {
    suppliers: PaginatedData<Contact>;
    filters: { search?: string };
};

export default function SupplierIndex({ suppliers, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/purchases/suppliers', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suppliers" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Suppliers</h1>
                        <p className="text-muted-foreground text-sm">Manage your suppliers</p>
                    </div>
                    <Button asChild>
                        <Link href="/purchases/suppliers/create">
                            <Plus className="mr-2 size-4" /> Add Supplier
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input placeholder="Search suppliers..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
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
                            {suppliers.data.length === 0 ? (
                                <tr><td colSpan={5} className="py-12 text-center text-muted-foreground">No suppliers found.</td></tr>
                            ) : (
                                suppliers.data.map((supplier) => (
                                    <tr key={supplier.id} className="border-    b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 font-medium">{supplier.name}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{supplier.email || '—'}</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{supplier.phone || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${supplier.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                {supplier.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/purchases/suppliers/${supplier.id}/edit`}>Edit</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {suppliers.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">Showing {suppliers.from} to {suppliers.to} of {suppliers.total}</p>
                        <div className="flex gap-2">
                            {suppliers.links.prev && <Button variant="outline" size="sm" asChild><Link href={suppliers.links.prev}>Previous</Link></Button>}
                            {suppliers.links.next && <Button variant="outline" size="sm" asChild><Link href={suppliers.links.next}>Next</Link></Button>}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

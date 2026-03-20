import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, TaxCode, PaginatedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tax Codes', href: '/accounting/tax-codes' },
];

type Props = {
    taxCodes: PaginatedData<TaxCode>;
    filters: { search?: string };
};

export default function TaxCodeIndex({ taxCodes, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/accounting/tax-codes', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tax Codes" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Tax Codes</h1>
                        <p className="text-muted-foreground text-sm">Manage tax rates for invoicing</p>
                    </div>
                    <Button asChild>
                        <Link href="/accounting/tax-codes/create">Add Tax Code</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input placeholder="Search..." className="pl-9" value={search} onChange={(e) => setSearch(e.target.value)} />
                    </div>
                </form>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Name</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Rate</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {taxCodes.data.length === 0 ? (
                                <tr><td colSpan={5} className="py-12 text-center text-muted-foreground">No tax codes found</td></tr>
                            ) : (
                                taxCodes.data.map((tc) => (
                                    <tr key={tc.id} className="border-b last:border-0 hover:bg-muted/30">
                                        <td className="py-3 px-4 font-medium">{tc.name}</td>
                                        <td className="py-3 px-4 text-right font-mono text-sm">{tc.rate}%</td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tc.description || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${tc.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>{tc.is_active ? 'Active' : 'Inactive'}</span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild><Link href={`/accounting/tax-codes/${tc.id}/edit`}>Edit</Link></Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

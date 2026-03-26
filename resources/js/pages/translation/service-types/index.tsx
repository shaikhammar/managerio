import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, ServiceType } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Service Types', href: '/translation/service-types' },
];

type BillingUnitOption = { value: string; label: string };

type Props = {
    serviceTypes: PaginatedData<ServiceType>;
    filters: { search?: string };
    billingUnits: BillingUnitOption[];
};

export default function ServiceTypeIndex({ serviceTypes, filters = {}, billingUnits }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const unitLabel = (value: string) => billingUnits.find((u) => u.value === value)?.label ?? value;

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/service-types', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Service Types" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Service Types</h1>
                        <p className="text-muted-foreground text-sm">Translation service categories and their default billing units</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/service-types/create">Add Service Type</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                        <Input
                            placeholder="Search by name or code..."
                            className="pl-9"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-muted/50 border-b">
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Name</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Code</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Default Unit</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Description</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Status</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {serviceTypes.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground py-12 text-center">
                                        No service types found
                                    </td>
                                </tr>
                            ) : (
                                serviceTypes.data.map((st) => (
                                    <tr key={st.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-medium">{st.name}</td>
                                        <td className="py-3 px-4 font-mono text-sm">{st.code}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm capitalize">
                                            {unitLabel(st.default_unit)}
                                        </td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{st.description || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span
                                                className={`text-xs ${st.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}
                                            >
                                                {st.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/translation/service-types/${st.id}/edit`}>Edit</Link>
                                            </Button>
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

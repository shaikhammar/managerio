import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, Package } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, FixedAsset, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fixed Assets', href: '/accounting/fixed-assets' },
];

type StatusOption = { value: string; label: string };

type Props = {
    assets: PaginatedData<FixedAsset & { accumulated_depreciation: string; book_value: string }>;
    filters: { search?: string; status?: string };
    statuses: StatusOption[];
};

const statusColors: Record<string, string> = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    retired: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    disposed: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
};

export default function FixedAssetIndex({ assets, filters, statuses }: Props) {
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/accounting/fixed-assets', { search, status: filters.status }, { preserveState: true });
    }

    function handleStatusChange(value: string) {
        router.get('/accounting/fixed-assets', { search, status: value === 'all' ? undefined : value }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fixed Assets" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Fixed Assets</h1>
                        <p className="text-muted-foreground text-sm">Track and depreciate your business assets</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/accounting/fixed-assets/run-depreciation-form">
                                Run Depreciation
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/accounting/fixed-assets/create">
                                <Plus className="mr-2 size-4" /> New Asset
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex gap-3">
                    <form onSubmit={handleSearch} className="flex-1 max-w-md">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Search assets..."
                                className="pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={filters.status || 'all'} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Asset</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Purchase Date</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Cost</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Accumulated Dep.</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Book Value</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {assets.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-12 text-center">
                                        <Package className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No fixed assets yet</p>
                                    </td>
                                </tr>
                            ) : (
                                assets.data.map((asset) => (
                                    <tr key={asset.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4">
                                            <Link
                                                href={`/accounting/fixed-assets/${asset.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {asset.name}
                                            </Link>
                                            {asset.asset_tag && (
                                                <p className="text-xs text-muted-foreground mt-0.5">{asset.asset_tag}</p>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{asset.purchase_date}</td>
                                        <td className="py-3 px-4 text-sm text-right">{format(Number(asset.purchase_cost))}</td>
                                        <td className="py-3 px-4 text-sm text-right text-muted-foreground">{format(Number(asset.accumulated_depreciation))}</td>
                                        <td className="py-3 px-4 text-sm text-right font-medium">{format(Number(asset.book_value))}</td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[asset.status] ?? ''}`}>
                                                {asset.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {assets.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {assets.from} to {assets.to} of {assets.total}
                        </p>
                        <div className="flex gap-2">
                            {assets.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={assets.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {assets.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={assets.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

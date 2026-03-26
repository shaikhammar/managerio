import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, RateCard } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Rate Cards', href: '/translation/rate-cards' },
];

type RateCardTypeOption = { value: string; label: string };

type Props = {
    rateCards: PaginatedData<RateCard>;
    filters: { search?: string; type?: string };
    rateCardTypes: RateCardTypeOption[];
};

const typeColors: Record<string, string> = {
    default: 'bg-blue-100 text-blue-700',
    client: 'bg-violet-100 text-violet-700',
    translator: 'bg-amber-100 text-amber-700',
};

export default function RateCardIndex({ rateCards, filters = {}, rateCardTypes }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');

    function applyFilters(overrides: Partial<{ search: string; type: string }> = {}) {
        const params: Record<string, string> = {};
        const merged = { search, type, ...overrides };

        if (merged.search) {
params.search = merged.search;
}

        if (merged.type) {
params.type = merged.type;
}

        router.get('/translation/rate-cards', params, { preserveState: true });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilters();
    }

    function handleTypeChange(value: string) {
        const newType = value === 'all' ? '' : value;
        setType(newType);
        applyFilters({ type: newType });
    }

    function pairLabel(rc: RateCard) {
        if (!rc.language_pair) {
return '—';
}

        const src = rc.language_pair.source_language;
        const tgt = rc.language_pair.target_language;

        if (!src || !tgt) {
return `Pair #${rc.language_pair_id}`;
}

        return `${src.code} → ${tgt.code}`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rate Cards" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Rate Cards</h1>
                        <p className="text-muted-foreground text-sm">Default, client, and translator rates per language pair and service type</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/rate-cards/create">Add Rate Card</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <form onSubmit={handleSearch}>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                            <Input
                                placeholder="Search by contact or service..."
                                className="w-72 pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={type || 'all'} onValueChange={handleTypeChange}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All types</SelectItem>
                            {rateCardTypes.map((t) => (
                                <SelectItem key={t.value} value={t.value}>
                                    {t.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-muted/50 border-b">
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Type</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Contact</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Language Pair</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Service</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Unit Rate</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Min Fee</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Rush ×</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Tiers</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Status</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rateCards.data.length === 0 ? (
                                <tr>
                                    <td colSpan={10} className="text-muted-foreground py-12 text-center">
                                        No rate cards found
                                    </td>
                                </tr>
                            ) : (
                                rateCards.data.map((rc) => (
                                    <tr key={rc.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4">
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${typeColors[rc.type] ?? 'bg-muted text-muted-foreground'}`}>
                                                {rc.type}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            {rc.contact?.name ?? <span className="text-muted-foreground italic">Business default</span>}
                                        </td>
                                        <td className="py-3 px-4 font-mono text-sm">{pairLabel(rc)}</td>
                                        <td className="py-3 px-4 text-sm">{rc.service_type?.name ?? '—'}</td>
                                        <td className="py-3 px-4 text-right text-sm tabular-nums">
                                            {parseFloat(rc.unit_rate).toFixed(4)}
                                            <span className="text-muted-foreground ml-1 text-xs">/{rc.unit}</span>
                                        </td>
                                        <td className="text-muted-foreground py-3 px-4 text-right text-sm tabular-nums">
                                            {rc.minimum_fee ? parseFloat(rc.minimum_fee).toFixed(2) : '—'}
                                        </td>
                                        <td className="text-muted-foreground py-3 px-4 text-right text-sm tabular-nums">
                                            {rc.rush_multiplier ? `×${parseFloat(rc.rush_multiplier).toFixed(2)}` : '—'}
                                        </td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">
                                            {rc.volume_tiers && rc.volume_tiers.length > 0 ? `${rc.volume_tiers.length} tier${rc.volume_tiers.length > 1 ? 's' : ''}` : '—'}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${rc.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                {rc.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/translation/rate-cards/${rc.id}/edit`}>Edit</Link>
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

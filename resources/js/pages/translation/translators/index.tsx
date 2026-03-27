import { Head, Link, router } from '@inertiajs/react';
import { Search, Star, UserCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, TranslatorProfile } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translators', href: '/translation/translators' },
];

type AvailabilityOption = { value: string; label: string; color: string };

type Props = {
    translators: PaginatedData<TranslatorProfile>;
    filters: { search?: string; availability?: string };
    availabilities: AvailabilityOption[];
};

const availabilityColors: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    busy: 'bg-yellow-100 text-yellow-700',
    on_leave: 'bg-gray-100 text-gray-600',
};

export default function TranslatorIndex({ translators, filters = {}, availabilities }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [availability, setAvailability] = useState(filters.availability || '');

    function applyFilters(overrides: Partial<{ search: string; availability: string }> = {}) {
        const params: Record<string, string> = {};
        const merged = { search, availability, ...overrides };

        if (merged.search) {
            params.search = merged.search;
        }

        if (merged.availability) {
            params.availability = merged.availability;
        }

        router.get('/translation/translators', params, { preserveState: true });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilters();
    }

    function handleAvailabilityChange(value: string) {
        const newVal = value === 'all' ? '' : value;
        setAvailability(newVal);
        applyFilters({ availability: newVal });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Translators" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Translators</h1>
                        <p className="text-muted-foreground text-sm">Extended vendor profiles for your translators</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/translators/create">New Profile</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <form onSubmit={handleSearch}>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                            <Input
                                className="w-64 pl-9"
                                placeholder="Search by name…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={availability || 'all'} onValueChange={handleAvailabilityChange}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            {availabilities.map((a) => (
                                <SelectItem key={a.value} value={a.value}>
                                    {a.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-gray-50/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Translator</th>
                                <th className="px-4 py-3 text-left font-medium">Status</th>
                                <th className="px-4 py-3 text-left font-medium">Language Pairs</th>
                                <th className="px-4 py-3 text-left font-medium">Services</th>
                                <th className="px-4 py-3 text-left font-medium">Rating</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {translators.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground py-8 text-center">
                                        No translator profiles found.
                                    </td>
                                </tr>
                            )}
                            {translators.data.map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50/50">
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <UserCheck className="text-muted-foreground size-4 shrink-0" />
                                            <div>
                                                <p className="font-medium">{t.contact?.name ?? '—'}</p>
                                                {t.contact?.email && (
                                                    <p className="text-muted-foreground text-xs">{t.contact.email}</p>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={availabilityColors[t.availability] ?? ''}>
                                            {availabilities.find((a) => a.value === t.availability)?.label ?? t.availability}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-muted-foreground">
                                            {t.language_pairs?.length
                                                ? t.language_pairs
                                                      .slice(0, 3)
                                                      .map(
                                                          (lp) =>
                                                              `${lp.source_language?.code ?? '?'} → ${lp.target_language?.code ?? '?'}`,
                                                      )
                                                      .join(', ') + (t.language_pairs.length > 3 ? ` +${t.language_pairs.length - 3}` : '')
                                                : '—'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="text-muted-foreground">
                                            {t.service_types?.length
                                                ? t.service_types
                                                      .slice(0, 2)
                                                      .map((s) => s.name)
                                                      .join(', ') + (t.service_types.length > 2 ? ` +${t.service_types.length - 2}` : '')
                                                : '—'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {t.quality_rating ? (
                                            <div className="flex items-center gap-1">
                                                {Array.from({ length: t.quality_rating }).map((_, i) => (
                                                    <Star key={i} className="size-3 fill-amber-400 text-amber-400" />
                                                ))}
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/translation/translators/${t.id}`}>View</Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {translators.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">
                            {translators.from}–{translators.to} of {translators.total}
                        </span>
                        <div className="flex gap-2">
                            {translators.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={translators.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {translators.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={translators.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

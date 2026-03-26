import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, LanguagePair, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Language Pairs', href: '/translation/language-pairs' },
];

type Props = {
    pairs: PaginatedData<LanguagePair>;
    filters: { search?: string };
};

export default function LanguagePairIndex({ pairs, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/language-pairs', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Language Pairs" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Language Pairs</h1>
                        <p className="text-muted-foreground text-sm">Source and target language combinations for translation projects</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/language-pairs/create">Add Language Pair</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                        <Input
                            placeholder="Search by language name or code..."
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
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Source Language</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Target Language</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Status</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pairs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="text-muted-foreground py-12 text-center">
                                        No language pairs found
                                    </td>
                                </tr>
                            ) : (
                                pairs.data.map((pair) => (
                                    <tr key={pair.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4">
                                            <span className="font-medium">{pair.source_language?.name}</span>
                                            <span className="text-muted-foreground ml-2 font-mono text-xs">
                                                {pair.source_language?.code}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className="font-medium">{pair.target_language?.name}</span>
                                            <span className="text-muted-foreground ml-2 font-mono text-xs">
                                                {pair.target_language?.code}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4">
                                            <span
                                                className={`text-xs ${pair.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}
                                            >
                                                {pair.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/translation/language-pairs/${pair.id}/edit`}>Edit</Link>
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

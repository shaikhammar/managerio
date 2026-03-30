import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, TranslationMemory } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Memories', href: '/translation/translation-memories' },
];

type Props = {
    translationMemories: PaginatedData<TranslationMemory>;
    filters: { search?: string };
};

export default function TranslationMemoryIndex({ translationMemories, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/translation-memories', { search }, { preserveState: true });
    }

    function handleDelete(tm: TranslationMemory) {
        if (!confirm(`Delete "${tm.name}"?`)) {
            return;
        }

        router.delete(`/translation/translation-memories/${tm.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Translation Memories" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Translation Memories</h1>
                        <p className="text-muted-foreground text-sm">Track TMs used per client and project</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/translation-memories/create">Add TM</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearch}>
                    <div className="relative max-w-md">
                        <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                        <Input
                            placeholder="Search by name…"
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
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Language Pair</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Software</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Client</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {translationMemories.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-12 text-center">
                                        No translation memories found
                                    </td>
                                </tr>
                            ) : (
                                translationMemories.data.map((tm) => (
                                    <tr key={tm.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-medium">{tm.name}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">
                                            {tm.source_language && tm.target_language
                                                ? `${tm.source_language.code.toUpperCase()} → ${tm.target_language.code.toUpperCase()}`
                                                : '—'}
                                        </td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{tm.software || '—'}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{tm.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/translation/translation-memories/${tm.id}/edit`}>Edit</Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => handleDelete(tm)}>
                                                    Delete
                                                </Button>
                                            </div>
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

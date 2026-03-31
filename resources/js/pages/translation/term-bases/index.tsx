import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, TermBase } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Term Bases', href: '/translation/term-bases' },
];

type Props = {
    termBases: PaginatedData<TermBase>;
    filters: { search?: string };
};

export default function TermBaseIndex({ termBases, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/term-bases', { search }, { preserveState: true });
    }

    function handleDelete(tb: TermBase) {
        if (!confirm(`Delete "${tb.name}"?`)) {
            return;
        }

        router.delete(`/translation/term-bases/${tb.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Term Bases" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Term Bases</h1>
                        <p className="text-muted-foreground text-sm">Client glossaries and terminology databases</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/term-bases/create">Add Term Base</Link>
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
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Subject Field</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Client</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {termBases.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="text-muted-foreground py-12 text-center">
                                        No term bases found
                                    </td>
                                </tr>
                            ) : (
                                termBases.data.map((tb) => (
                                    <tr key={tb.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-medium">{tb.name}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{tb.subject_field || '—'}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{tb.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/translation/term-bases/${tb.id}/edit`}>Edit</Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => handleDelete(tb)}>
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

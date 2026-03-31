import { Head, Link, router } from '@inertiajs/react';
import { Download, Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, StyleGuide } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Style Guides', href: '/translation/style-guides' },
];

type Props = {
    styleGuides: PaginatedData<StyleGuide>;
    filters: { search?: string };
};

function formatBytes(bytes: number | null): string {
    if (!bytes) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function StyleGuideIndex({ styleGuides, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/style-guides', { search }, { preserveState: true });
    }

    function handleDelete(sg: StyleGuide) {
        if (!confirm(`Delete "${sg.name}"?`)) {
            return;
        }

        router.delete(`/translation/style-guides/${sg.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Style Guides" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Style Guides</h1>
                        <p className="text-muted-foreground text-sm">Client style guides associated with projects</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/style-guides/create">Add Style Guide</Link>
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
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Client</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">File</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {styleGuides.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="text-muted-foreground py-12 text-center">
                                        No style guides found
                                    </td>
                                </tr>
                            ) : (
                                styleGuides.data.map((sg) => (
                                    <tr key={sg.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-medium">{sg.name}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{sg.contact?.name || '—'}</td>
                                        <td className="py-3 px-4 text-sm">
                                            {sg.file_path ? (
                                                <a
                                                    href={`/storage/${sg.file_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-primary inline-flex items-center gap-1 hover:underline"
                                                >
                                                    <Download className="size-3" />
                                                    {sg.file_name} ({formatBytes(sg.file_size)})
                                                </a>
                                            ) : (
                                                <span className="text-muted-foreground">No file</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/translation/style-guides/${sg.id}/edit`}>Edit</Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => handleDelete(sg)}>
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

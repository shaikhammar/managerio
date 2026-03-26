import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Language, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Languages', href: '/translation/languages' },
];

type Props = {
    languages: PaginatedData<Language>;
    filters: { search?: string };
};

export default function LanguageIndex({ languages, filters = {} }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/translation/languages', { search }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Languages" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Languages</h1>
                        <p className="text-muted-foreground text-sm">ISO 639 language codes used in translation projects</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/languages/create">Add Language</Link>
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
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Code</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Name</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Native Name</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Status</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {languages.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground py-12 text-center">
                                        No languages found
                                    </td>
                                </tr>
                            ) : (
                                languages.data.map((lang) => (
                                    <tr key={lang.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-mono text-sm font-medium">{lang.code}</td>
                                        <td className="py-3 px-4 font-medium">{lang.name}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">{lang.native_name || '—'}</td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${lang.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                {lang.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/translation/languages/${lang.id}/edit`}>Edit</Link>
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

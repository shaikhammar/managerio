import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData, Project } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Projects', href: '/translation/projects' },
];

type StatusOption = { value: string; label: string; color: string };

type Props = {
    projects: PaginatedData<Project>;
    filters: { search?: string; status?: string };
    statuses: StatusOption[];
};

const statusColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    review: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
    delivered: 'bg-teal-100 text-teal-700',
    invoiced: 'bg-indigo-100 text-indigo-700',
    closed: 'bg-gray-100 text-gray-600',
};

export default function ProjectIndex({ projects, filters = {}, statuses }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    function applyFilters(overrides: Partial<{ search: string; status: string }> = {}) {
        const params: Record<string, string> = {};
        const merged = { search, status, ...overrides };

        if (merged.search) {
params.search = merged.search;
}

        if (merged.status) {
params.status = merged.status;
}

        router.get('/translation/projects', params, { preserveState: true });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilters();
    }

    function handleStatusChange(value: string) {
        const newStatus = value === 'all' ? '' : value;
        setStatus(newStatus);
        applyFilters({ status: newStatus });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
                        <p className="text-muted-foreground text-sm">Translation projects and jobs</p>
                    </div>
                    <Button asChild>
                        <Link href="/translation/projects/create">New Project</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-3">
                    <form onSubmit={handleSearch}>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                            <Input
                                placeholder="Search by name or reference..."
                                className="w-72 pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={status || 'all'} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-muted/50 border-b">
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Reference</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Name</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Client</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Source</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Service</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Deadline</th>
                                <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Status</th>
                                <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {projects.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="text-muted-foreground py-12 text-center">
                                        No projects found
                                    </td>
                                </tr>
                            ) : (
                                projects.data.map((project) => (
                                    <tr key={project.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 font-mono text-sm">{project.reference ?? '—'}</td>
                                        <td className="py-3 px-4 text-sm font-medium">
                                            <Link href={`/translation/projects/${project.id}`} className="hover:underline">
                                                {project.name}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-sm">{project.contact?.name ?? '—'}</td>
                                        <td className="py-3 px-4 font-mono text-sm">{project.source_language?.code ?? '—'}</td>
                                        <td className="py-3 px-4 text-sm">{project.service_type?.name ?? '—'}</td>
                                        <td className="text-muted-foreground py-3 px-4 text-sm">
                                            {project.deadline ? new Date(project.deadline).toLocaleDateString() : '—'}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${statusColors[project.status] ?? 'bg-muted text-muted-foreground'}`}
                                            >
                                                {project.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/translation/projects/${project.id}`}>View</Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {projects.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {projects.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

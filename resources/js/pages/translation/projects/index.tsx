import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Kanban, LayoutList, Search, Users } from 'lucide-react';
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
type SimpleOption = { id: number; name: string };
type LPOption = { id: number; source_language_id: number; target_language_id: number; source_language?: { code: string }; target_language?: { code: string } };

type Props = {
    projects: PaginatedData<Project>;
    filters: {
        search?: string;
        status?: string;
        client_id?: string;
        service_type_id?: string;
        language_pair_id?: string;
        deadline_from?: string;
        deadline_to?: string;
    };
    statuses: StatusOption[];
    customers: SimpleOption[];
    serviceTypes: SimpleOption[];
    languagePairs: LPOption[];
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

export default function ProjectIndex({ projects, filters = {}, statuses, customers, serviceTypes, languagePairs }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [clientId, setClientId] = useState(filters.client_id || '');
    const [serviceTypeId, setServiceTypeId] = useState(filters.service_type_id || '');
    const [languagePairId, setLanguagePairId] = useState(filters.language_pair_id || '');
    const [deadlineFrom, setDeadlineFrom] = useState(filters.deadline_from || '');
    const [deadlineTo, setDeadlineTo] = useState(filters.deadline_to || '');

    function applyFilters(overrides: Record<string, string> = {}) {
        const merged = {
            search,
            status,
            client_id: clientId,
            service_type_id: serviceTypeId,
            language_pair_id: languagePairId,
            deadline_from: deadlineFrom,
            deadline_to: deadlineTo,
            ...overrides,
        };
        const params: Record<string, string> = {};

        for (const [k, v] of Object.entries(merged)) {
            if (v) {
                params[k] = v;
            }
        }

        router.get('/translation/projects', params, { preserveState: true });
    }

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        applyFilters();
    }

    function handleSelect(field: string, value: string, setter: (v: string) => void) {
        const normalized = value === 'all' ? '' : value;
        setter(normalized);
        applyFilters({ [field]: normalized });
    }

    function handleDateChange(field: string, value: string, setter: (v: string) => void) {
        setter(value);
        applyFilters({ [field]: value });
    }

    function clearFilters() {
        setSearch('');
        setStatus('');
        setClientId('');
        setServiceTypeId('');
        setLanguagePairId('');
        setDeadlineFrom('');
        setDeadlineTo('');
        router.get('/translation/projects', {}, { preserveState: true });
    }

    const hasActiveFilters = !!(search || status || clientId || serviceTypeId || languagePairId || deadlineFrom || deadlineTo);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
                        <p className="text-muted-foreground text-sm">Translation projects and jobs</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* View toggle */}
                        <div className="flex overflow-hidden rounded-lg border">
                            <Button variant="default" size="sm" className="rounded-none border-0" disabled>
                                <LayoutList className="size-4" />
                            </Button>
                            <Button variant="ghost" size="sm" className="rounded-none border-0 border-l" asChild>
                                <Link href="/translation/projects/board">
                                    <Kanban className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="sm" className="rounded-none border-0 border-l" asChild>
                                <Link href="/translation/projects/calendar">
                                    <CalendarDays className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="sm" className="rounded-none border-0 border-l" asChild>
                                <Link href="/translation/projects/capacity">
                                    <Users className="size-4" />
                                </Link>
                            </Button>
                        </div>
                        <Button asChild>
                            <Link href="/translation/projects/create">New Project</Link>
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <form onSubmit={handleSearch}>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                            <Input
                                placeholder="Search by name or reference..."
                                className="w-64 pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>

                    <Select value={status || 'all'} onValueChange={(v) => handleSelect('status', v, setStatus)}>
                        <SelectTrigger className="w-40">
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

                    <Select value={clientId || 'all'} onValueChange={(v) => handleSelect('client_id', v, setClientId)}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All clients" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All clients</SelectItem>
                            {customers.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={serviceTypeId || 'all'} onValueChange={(v) => handleSelect('service_type_id', v, setServiceTypeId)}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All services" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All services</SelectItem>
                            {serviceTypes.map((s) => (
                                <SelectItem key={s.id} value={String(s.id)}>
                                    {s.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={languagePairId || 'all'} onValueChange={(v) => handleSelect('language_pair_id', v, setLanguagePairId)}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All lang pairs" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All lang pairs</SelectItem>
                            {languagePairs.map((lp) => (
                                <SelectItem key={lp.id} value={String(lp.id)}>
                                    {lp.source_language?.code ?? '?'} → {lp.target_language?.code ?? '?'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <div className="flex items-center gap-1.5">
                        <Input
                            type="date"
                            className="w-36 text-sm"
                            value={deadlineFrom}
                            onChange={(e) => handleDateChange('deadline_from', e.target.value, setDeadlineFrom)}
                            title="Deadline from"
                        />
                        <span className="text-muted-foreground text-sm">—</span>
                        <Input
                            type="date"
                            className="w-36 text-sm"
                            value={deadlineTo}
                            onChange={(e) => handleDateChange('deadline_to', e.target.value, setDeadlineTo)}
                            title="Deadline to"
                        />
                    </div>

                    {hasActiveFilters && (
                        <Button variant="ghost" size="sm" onClick={clearFilters}>
                            Clear filters
                        </Button>
                    )}
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
                                projects.data.map((project) => {
                                    const deadlineDate = project.deadline ? new Date(project.deadline) : null;
                                    const isOverdue =
                                        deadlineDate &&
                                        deadlineDate < new Date() &&
                                        !['completed', 'delivered', 'invoiced', 'closed'].includes(project.status);

                                    return (
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
                                            <td
                                                className={`py-3 px-4 text-sm ${isOverdue ? 'font-medium text-red-600' : 'text-muted-foreground'}`}
                                            >
                                                {project.deadline ? new Date(project.deadline).toLocaleDateString() : '—'}
                                                {isOverdue && <span className="ml-1 text-xs">(overdue)</span>}
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
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                {projects.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {projects.links.prev && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={projects.links.prev}>Previous</Link>
                            </Button>
                        )}
                        <span className="flex items-center px-2 text-sm">
                            Page {projects.current_page} of {projects.last_page}
                        </span>
                        {projects.links.next && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={projects.links.next}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

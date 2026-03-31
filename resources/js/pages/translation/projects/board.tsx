import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Kanban, LayoutList, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Project } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Projects', href: '/translation/projects' },
    { title: 'Board', href: '/translation/projects/board' },
];

type BoardColumn = {
    status: string;
    label: string;
    color: string;
    projects: Project[];
};

type Props = {
    board: Record<string, BoardColumn>;
    filters: { client_id?: string; service_type_id?: string; language_pair_id?: string };
};

const statusColors: Record<string, string> = {
    new: 'border-l-blue-400',
    in_progress: 'border-l-yellow-400',
    review: 'border-l-purple-400',
    completed: 'border-l-green-400',
    delivered: 'border-l-teal-400',
    invoiced: 'border-l-indigo-400',
    closed: 'border-l-gray-400',
};

const statusBadgeColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    review: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
    delivered: 'bg-teal-100 text-teal-700',
    invoiced: 'bg-indigo-100 text-indigo-700',
    closed: 'bg-gray-100 text-gray-600',
};

const columnHeaderColors: Record<string, string> = {
    new: 'bg-blue-50 border-blue-200',
    in_progress: 'bg-yellow-50 border-yellow-200',
    review: 'bg-purple-50 border-purple-200',
    completed: 'bg-green-50 border-green-200',
    delivered: 'bg-teal-50 border-teal-200',
    invoiced: 'bg-indigo-50 border-indigo-200',
    closed: 'bg-gray-50 border-gray-200',
};

function isOverdue(deadline: string | null, status: string): boolean {
    if (!deadline) {
        return false;
    }

    const done = ['completed', 'delivered', 'invoiced', 'closed'];

    if (done.includes(status)) {
        return false;
    }

    return new Date(deadline) < new Date();
}

export default function ProjectBoard({ board }: Props) {
    const columns = Object.values(board);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project Board" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Project Board</h1>
                        <p className="text-muted-foreground text-sm">Kanban view of active projects by status</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects">
                                <LayoutList className="mr-1 size-4" />
                                List
                            </Link>
                        </Button>
                        <Button variant="default" size="sm" disabled>
                            <Kanban className="mr-1 size-4" />
                            Board
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects/calendar">
                                <CalendarDays className="mr-1 size-4" />
                                Calendar
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects/capacity">
                                <Users className="mr-1 size-4" />
                                Capacity
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex gap-4 overflow-x-auto pb-4">
                    {columns.map((column) => (
                        <div key={column.status} className="flex w-72 shrink-0 flex-col gap-3">
                            <div
                                className={`flex items-center justify-between rounded-lg border px-3 py-2 ${columnHeaderColors[column.status] ?? 'bg-muted border-border'}`}
                            >
                                <span className="text-sm font-semibold">{column.label}</span>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs font-bold ${statusBadgeColors[column.status] ?? 'bg-muted text-muted-foreground'}`}
                                >
                                    {column.projects.length}
                                </span>
                            </div>

                            <div className="flex flex-col gap-2">
                                {column.projects.length === 0 ? (
                                    <div className="text-muted-foreground rounded-lg border border-dashed py-6 text-center text-sm">
                                        No projects
                                    </div>
                                ) : (
                                    column.projects.map((project) => {
                                        const overdue = isOverdue(project.deadline, project.status);

                                        return (
                                            <Link
                                                key={project.id}
                                                href={`/translation/projects/${project.id}`}
                                                className={`block rounded-lg border border-l-4 bg-white p-3 shadow-sm transition-shadow hover:shadow-md dark:bg-gray-900 ${statusColors[project.status] ?? 'border-l-gray-300'} ${overdue ? 'border-red-200 bg-red-50 dark:bg-red-950/20' : ''}`}
                                            >
                                                <div className="mb-1 flex items-start justify-between gap-1">
                                                    <span className="text-sm font-medium leading-tight">{project.name}</span>
                                                    {overdue && (
                                                        <span className="shrink-0 rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600">
                                                            Overdue
                                                        </span>
                                                    )}
                                                </div>
                                                {project.reference && (
                                                    <p className="text-muted-foreground mb-1 font-mono text-xs">{project.reference}</p>
                                                )}
                                                {project.contact && (
                                                    <p className="text-muted-foreground mb-1 text-xs">{project.contact.name}</p>
                                                )}
                                                <div className="mt-2 flex items-center justify-between gap-2 text-xs">
                                                    {project.source_language && (
                                                        <span className="text-muted-foreground font-mono">{project.source_language.code}</span>
                                                    )}
                                                    {project.deadline && (
                                                        <span className={overdue ? 'font-medium text-red-600' : 'text-muted-foreground'}>
                                                            {new Date(project.deadline).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                </div>
                                            </Link>
                                        );
                                    })
                                )}
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                className="w-full"
                                onClick={() => router.get('/translation/projects/create')}
                            >
                                + Add project
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}

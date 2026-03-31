import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Kanban, LayoutList, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Projects', href: '/translation/projects' },
    { title: 'Calendar', href: '/translation/projects/calendar' },
];

type CalendarProject = {
    id: number;
    name: string;
    reference: string | null;
    deadline: string;
    status: string;
    status_label: string;
    status_color: string;
    client: string | null;
    is_overdue: boolean;
};

type Props = {
    projects: CalendarProject[];
    year: number;
    month: number;
};

const statusDotColors: Record<string, string> = {
    new: 'bg-blue-400',
    in_progress: 'bg-yellow-400',
    review: 'bg-purple-400',
    completed: 'bg-green-400',
    delivered: 'bg-teal-400',
    invoiced: 'bg-indigo-400',
    closed: 'bg-gray-400',
};

const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function buildCalendarDays(year: number, month: number): (number | null)[] {
    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    const days: (number | null)[] = [];

    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        days.push(d);
    }

    return days;
}

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

export default function ProjectCalendar({ projects, year, month }: Props) {
    const days = buildCalendarDays(year, month);
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;

    // Group projects by deadline date
    const byDate: Record<string, CalendarProject[]> = {};

    for (const project of projects) {
        if (!byDate[project.deadline]) {
            byDate[project.deadline] = [];
        }

        byDate[project.deadline].push(project);
    }

    function navigate(dir: -1 | 1) {
        let newMonth = month + dir;
        let newYear = year;

        if (newMonth < 1) {
 newMonth = 12; newYear--; 
}

        if (newMonth > 12) {
 newMonth = 1; newYear++; 
}

        router.get('/translation/projects/calendar', { year: newYear, month: newMonth }, { preserveState: false });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project Calendar" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Project Calendar</h1>
                        <p className="text-muted-foreground text-sm">Project deadlines for {MONTH_NAMES[month - 1]} {year}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects">
                                <LayoutList className="mr-1 size-4" />
                                List
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects/board">
                                <Kanban className="mr-1 size-4" />
                                Board
                            </Link>
                        </Button>
                        <Button variant="default" size="sm" disabled>
                            Calendar
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects/capacity">
                                <Users className="mr-1 size-4" />
                                Capacity
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Month navigator */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" onClick={() => navigate(-1)}>
                        <ChevronLeft className="size-4" />
                    </Button>
                    <span className="text-lg font-semibold">
                        {MONTH_NAMES[month - 1]} {year}
                    </span>
                    <Button variant="outline" size="sm" onClick={() => navigate(1)}>
                        <ChevronRight className="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.get('/translation/projects/calendar', {
                            year: today.getFullYear(),
                            month: today.getMonth() + 1,
                        })}
                    >
                        Today
                    </Button>
                </div>

                {/* Calendar grid */}
                <div className="overflow-hidden rounded-lg border">
                    <div className="grid grid-cols-7 border-b">
                        {DAY_NAMES.map((d) => (
                            <div key={d} className="bg-muted/50 px-2 py-2 text-center text-xs font-medium">
                                {d}
                            </div>
                        ))}
                    </div>
                    <div className="grid grid-cols-7">
                        {days.map((day, idx) => {
                            const dateStr = day ? `${year}-${pad(month)}-${pad(day)}` : null;
                            const dayProjects = dateStr ? (byDate[dateStr] ?? []) : [];
                            const isToday = dateStr === todayStr;

                            return (
                                <div
                                    key={idx}
                                    className={`min-h-24 border-b border-r p-1.5 last:border-r-0 ${!day ? 'bg-muted/20' : ''} ${isToday ? 'bg-blue-50/60 dark:bg-blue-950/20' : ''}`}
                                >
                                    {day && (
                                        <>
                                            <div
                                                className={`mb-1 flex size-6 items-center justify-center rounded-full text-xs font-medium ${isToday ? 'bg-blue-500 text-white' : 'text-muted-foreground'}`}
                                            >
                                                {day}
                                            </div>
                                            <div className="flex flex-col gap-1">
                                                {dayProjects.slice(0, 3).map((project) => (
                                                    <Link
                                                        key={project.id}
                                                        href={`/translation/projects/${project.id}`}
                                                        className={`flex items-center gap-1 rounded px-1 py-0.5 text-xs leading-tight transition-colors hover:opacity-80 ${project.is_overdue ? 'bg-red-100 text-red-700 dark:bg-red-950/40' : 'bg-muted hover:bg-muted/80'}`}
                                                    >
                                                        <span
                                                            className={`inline-block size-1.5 shrink-0 rounded-full ${statusDotColors[project.status] ?? 'bg-gray-400'}`}
                                                        />
                                                        <span className="truncate">{project.name}</span>
                                                    </Link>
                                                ))}
                                                {dayProjects.length > 3 && (
                                                    <span className="text-muted-foreground px-1 text-xs">
                                                        +{dayProjects.length - 3} more
                                                    </span>
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Legend */}
                <div className="flex flex-wrap gap-4 text-xs">
                    {Object.entries(statusDotColors).map(([status, color]) => (
                        <div key={status} className="flex items-center gap-1.5">
                            <span className={`inline-block size-2 rounded-full ${color}`} />
                            <span className="text-muted-foreground capitalize">{status.replace('_', ' ')}</span>
                        </div>
                    ))}
                    <div className="flex items-center gap-1.5">
                        <span className="inline-block h-3 w-4 rounded bg-red-100" />
                        <span className="text-muted-foreground">Overdue</span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Kanban, LayoutList } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Projects', href: '/translation/projects' },
    { title: 'Capacity', href: '/translation/projects/capacity' },
];

type LanguagePairSummary = { id: number; label: string };

type TranslatorCapacity = {
    id: number;
    contact_id: number;
    name: string;
    availability: string | null;
    availability_label: string | null;
    weekly_capacity: number | null;
    pipeline_words: number;
    utilization_percent: number | null;
    language_pairs: LanguagePairSummary[];
};

type Props = {
    translators: TranslatorCapacity[];
};

const availabilityColors: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    busy: 'bg-yellow-100 text-yellow-700',
    on_leave: 'bg-gray-100 text-gray-600',
};

function utilizationColor(percent: number): string {
    if (percent >= 90) {
        return 'bg-red-500';
    }

    if (percent >= 70) {
        return 'bg-yellow-400';
    }

    return 'bg-green-500';
}

function formatWords(n: number): string {
    if (n >= 1_000_000) {
        return `${(n / 1_000_000).toFixed(1)}M`;
    }

    if (n >= 1_000) {
        return `${(n / 1_000).toFixed(1)}k`;
    }

    return String(n);
}

export default function ProjectCapacity({ translators }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Translator Capacity" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Translator Capacity</h1>
                        <p className="text-muted-foreground text-sm">Pipeline word volume vs. weekly capacity per translator</p>
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
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/translation/projects/calendar">
                                <CalendarDays className="mr-1 size-4" />
                                Calendar
                            </Link>
                        </Button>
                        <Button variant="default" size="sm" disabled>
                            Capacity
                        </Button>
                    </div>
                </div>

                {translators.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <p className="text-muted-foreground text-sm">No translator profiles found.</p>
                        <Button asChild className="mt-4" size="sm" variant="outline">
                            <Link href="/translation/translators">Manage Translators</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg border">
                        <table className="w-full">
                            <thead>
                                <tr className="bg-muted/50 border-b">
                                    <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Translator</th>
                                    <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Availability</th>
                                    <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Language Pairs</th>
                                    <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Pipeline Words</th>
                                    <th className="text-muted-foreground py-3 px-4 text-right text-sm font-medium">Weekly Capacity</th>
                                    <th className="text-muted-foreground py-3 px-4 text-left text-sm font-medium">Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                {translators.map((t) => (
                                    <tr key={t.id} className="hover:bg-muted/30 border-b last:border-0">
                                        <td className="py-3 px-4 text-sm font-medium">
                                            <Link
                                                href={`/translation/translators/${t.id}`}
                                                className="hover:underline"
                                            >
                                                {t.name}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4">
                                            {t.availability_label ? (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${availabilityColors[t.availability ?? ''] ?? 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {t.availability_label}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground text-sm">—</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4">
                                            <div className="flex flex-wrap gap-1">
                                                {t.language_pairs.slice(0, 4).map((lp) => (
                                                    <span
                                                        key={lp.id}
                                                        className="bg-muted rounded px-1.5 py-0.5 font-mono text-xs"
                                                    >
                                                        {lp.label}
                                                    </span>
                                                ))}
                                                {t.language_pairs.length > 4 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        +{t.language_pairs.length - 4}
                                                    </span>
                                                )}
                                                {t.language_pairs.length === 0 && (
                                                    <span className="text-muted-foreground text-xs">—</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <span className={`text-sm font-medium ${t.pipeline_words > 0 ? '' : 'text-muted-foreground'}`}>
                                                {formatWords(t.pipeline_words)}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            {t.weekly_capacity != null ? (
                                                <span className="text-sm">{formatWords(t.weekly_capacity)} / week</span>
                                            ) : (
                                                <span className="text-muted-foreground text-xs italic">Not set</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4">
                                            {t.utilization_percent != null ? (
                                                <div className="flex items-center gap-2">
                                                    <div className="h-2 w-24 overflow-hidden rounded-full bg-gray-200">
                                                        <div
                                                            className={`h-full rounded-full transition-all ${utilizationColor(t.utilization_percent)}`}
                                                            style={{ width: `${t.utilization_percent}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-xs font-medium tabular-nums">
                                                        {t.utilization_percent}%
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground text-xs italic">No capacity set</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <p className="text-muted-foreground text-xs">
                    Pipeline includes projects in New, In Progress, Review, and Completed statuses.
                    Set weekly capacity on each{' '}
                    <Link href="/translation/translators" className="underline">
                        translator profile
                    </Link>
                    .
                </p>
            </div>
        </AppLayout>
    );
}

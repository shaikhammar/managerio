import { Head, Link, router } from '@inertiajs/react';
import { History } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audit Log', href: '/audit-log' },
];

const eventColors: Record<string, string> = {
    created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    updated: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    deleted: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

type AuditEntry = {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number;
    auditable_label: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
};

type Props = {
    logs: PaginatedData<AuditEntry>;
    filters: { event?: string; type?: string };
};

function modelLabel(type: string): string {
    const parts = type.split('\\');
    const name = parts[parts.length - 1] ?? type;

    return name.replace(/([A-Z])/g, ' $1').trim();
}

function diffSummary(entry: AuditEntry): string {
    if (entry.event === 'created') {
        return 'Record created';
    }

    if (entry.event === 'deleted') {
        return 'Record deleted';
    }

    if (entry.new_values && Object.keys(entry.new_values).length > 0) {
        const fields = Object.keys(entry.new_values).join(', ');

        return `Changed: ${fields}`;
    }

    return 'Updated';
}

export default function AuditLogIndex({ logs, filters }: Props) {
    function handleEventChange(event: string) {
        router.get('/audit-log', { event: event === 'all' ? undefined : event, type: filters.type }, { preserveState: true });
    }

    function handleTypeChange(type: string) {
        router.get('/audit-log', { event: filters.event, type: type === 'all' ? undefined : type }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Log" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Audit Log</h1>
                    <p className="text-muted-foreground text-sm">Track who changed what and when across all records.</p>
                </div>

                <div className="flex gap-3">
                    <Select value={filters.event || 'all'} onValueChange={handleEventChange}>
                        <SelectTrigger className="w-[160px]">
                            <SelectValue placeholder="All Events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Events</SelectItem>
                            <SelectItem value="created">Created</SelectItem>
                            <SelectItem value="updated">Updated</SelectItem>
                            <SelectItem value="deleted">Deleted</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={filters.type || 'all'} onValueChange={handleTypeChange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="All Types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Types</SelectItem>
                            <SelectItem value="Invoice">Invoice / Quote</SelectItem>
                            <SelectItem value="Contact">Contact</SelectItem>
                            <SelectItem value="Payment">Payment</SelectItem>
                            <SelectItem value="JournalEntry">Journal Entry</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">When</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">User</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Event</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Record</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Summary</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <History className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No audit entries found</p>
                                    </td>
                                </tr>
                            ) : (
                                logs.data.map((entry) => (
                                    <tr key={entry.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 text-sm text-muted-foreground whitespace-nowrap">
                                            {new Date(entry.created_at).toLocaleString()}
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            {entry.user?.name ?? <span className="text-muted-foreground italic">System</span>}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${eventColors[entry.event] || ''}`}>
                                                {entry.event}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            <span className="text-muted-foreground text-xs">{modelLabel(entry.auditable_type)}</span>
                                            {entry.auditable_label && (
                                                <span className="ml-1.5 font-mono font-medium">{entry.auditable_label}</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">
                                            {diffSummary(entry)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {logs.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {logs.from} to {logs.to} of {logs.total}
                        </p>
                        <div className="flex gap-2">
                            {logs.links.prev && (<Button variant="outline" size="sm" asChild><Link href={logs.links.prev}>Previous</Link></Button>)}
                            {logs.links.next && (<Button variant="outline" size="sm" asChild><Link href={logs.links.next}>Next</Link></Button>)}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

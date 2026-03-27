import { Head, Link } from '@inertiajs/react';
import { Plus, CheckCircle2, Landmark } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BankReconciliation, BreadcrumbItem, PaginatedData } from '@/types';

type Props = {
    reconciliations: PaginatedData<BankReconciliation>;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Reconciliations', href: '/banking/reconciliations' },
];

export default function ReconciliationIndex({ reconciliations }: Props) {
    const { format } = useCurrency();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bank Reconciliations" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Bank Reconciliations</h1>
                        <p className="text-muted-foreground text-sm">Match bank statements with your accounting records</p>
                    </div>
                    <Button asChild>
                        <Link href="/banking/reconciliations/create">
                            <Plus className="mr-2 size-4" /> New Reconciliation
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Bank Account</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Statement Date</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Statement Balance</th>
                                <th className="text-center py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="w-20"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {reconciliations.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <CheckCircle2 className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No reconciliations recorded yet</p>
                                    </td>
                                </tr>
                            ) : (
                                reconciliations.data.map((rec) => (
                                    <tr key={rec.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 text-sm font-medium">
                                            <div className="flex items-center gap-2">
                                                <Landmark className="size-3 text-muted-foreground" />
                                                {rec.bank_account?.name}
                                            </div>
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{rec.statement_date}</td>
                                        <td className="py-3 px-4 text-right text-sm font-medium">
                                            {format(rec.statement_balance)}
                                        </td>
                                        <td className="py-3 px-4 text-center">
                                            {rec.is_completed ? (
                                                <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    Completed
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                    In Progress
                                                </span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/banking/reconciliations/${rec.id}`}>View</Link>
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

import { Head, Link, router } from '@inertiajs/react';
import { Edit, TrendingUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, BudgetReport } from '@/types';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Budgets', href: '/accounting/budgets' },
];

type Props = {
    report: BudgetReport;
    year: number;
};

export default function BudgetIndex({ report, year }: Props) {
    const { format } = useCurrency();

    function changeYear(delta: number) {
        router.get('/accounting/budgets', { year: year + delta });
    }

    const hasAnyBudget = report.accounts.some((r) =>
        Object.values(r.months).some((m) => m.budgeted > 0)
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Budget vs Actual ${year}`} />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Budget vs Actual</h1>
                        <p className="text-muted-foreground text-sm">Compare budgeted amounts against actual transactions</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-1">
                            <Button variant="outline" size="sm" onClick={() => changeYear(-1)}>‹</Button>
                            <span className="px-3 text-sm font-medium">{year}</span>
                            <Button variant="outline" size="sm" onClick={() => changeYear(1)}>›</Button>
                        </div>
                        <Button asChild>
                            <Link href={`/accounting/budgets/edit?year=${year}`}>
                                <Edit className="mr-2 size-4" /> Edit Budget
                            </Link>
                        </Button>
                    </div>
                </div>

                {!hasAnyBudget ? (
                    <div className="rounded-lg border py-16 text-center">
                        <TrendingUp className="size-12 mx-auto mb-3 text-muted-foreground/30" />
                        <p className="font-medium">No budget set for {year}</p>
                        <p className="text-muted-foreground text-sm mt-1">Set monthly budget targets to compare against actuals.</p>
                        <Button className="mt-4" asChild>
                            <Link href={`/accounting/budgets/edit?year=${year}`}>Set Budget</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-lg border overflow-x-auto">
                        <table className="w-full text-sm min-w-[900px]">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="text-left py-2 px-3 font-medium text-muted-foreground w-[160px]">Account</th>
                                    {MONTHS.map((m) => (
                                        <th key={m} className="text-right py-2 px-2 font-medium text-muted-foreground w-[72px]">{m}</th>
                                    ))}
                                    <th className="text-right py-2 px-3 font-medium text-muted-foreground w-[90px]">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.accounts.map(({ account, months, total_budgeted, total_actual, total_variance }) => (
                                    <>
                                        {/* Budget row */}
                                        <tr key={`${account.id}-budget`} className="border-b border-dashed">
                                            <td className="py-1.5 px-3 font-medium" rowSpan={3}>
                                                <div>
                                                    <p>{account.name}</p>
                                                    <p className="font-mono text-[10px] text-muted-foreground">{account.code}</p>
                                                </div>
                                            </td>
                                            {Object.values(months).map((m, idx) => (
                                                <td key={idx} className="py-1.5 px-2 text-right text-muted-foreground text-xs">
                                                    {m.budgeted > 0 ? format(m.budgeted) : '—'}
                                                </td>
                                            ))}
                                            <td className="py-1.5 px-3 text-right text-muted-foreground text-xs font-medium">
                                                {total_budgeted > 0 ? format(total_budgeted) : '—'}
                                            </td>
                                        </tr>
                                        {/* Actual row */}
                                        <tr key={`${account.id}-actual`} className="border-b border-dashed">
                                            {Object.values(months).map((m, idx) => (
                                                <td key={idx} className="py-1.5 px-2 text-right text-xs">
                                                    {m.actual !== 0 ? format(m.actual) : '—'}
                                                </td>
                                            ))}
                                            <td className="py-1.5 px-3 text-right text-xs font-medium">
                                                {total_actual !== 0 ? format(total_actual) : '—'}
                                            </td>
                                        </tr>
                                        {/* Variance row */}
                                        <tr key={`${account.id}-variance`} className="border-b">
                                            {Object.values(months).map((m, idx) => (
                                                <td key={idx} className={`py-1.5 px-2 text-right text-xs font-medium ${
                                                    m.variance > 0 ? 'text-emerald-600 dark:text-emerald-400' :
                                                    m.variance < 0 ? 'text-red-600 dark:text-red-400' :
                                                    'text-muted-foreground'
                                                }`}>
                                                    {m.variance !== 0 ? format(m.variance) : '—'}
                                                </td>
                                            ))}
                                            <td className={`py-1.5 px-3 text-right text-xs font-bold ${
                                                total_variance > 0 ? 'text-emerald-600 dark:text-emerald-400' :
                                                total_variance < 0 ? 'text-red-600 dark:text-red-400' :
                                                'text-muted-foreground'
                                            }`}>
                                                {total_variance !== 0 ? format(total_variance) : '—'}
                                            </td>
                                        </tr>
                                    </>
                                ))}
                            </tbody>
                        </table>
                        <div className="px-3 py-2 text-xs text-muted-foreground border-t flex gap-4">
                            <span><span className="font-medium">Row 1:</span> Budgeted</span>
                            <span><span className="font-medium">Row 2:</span> Actual</span>
                            <span><span className="font-medium text-emerald-600">Row 3:</span> Variance (green = under budget)</span>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

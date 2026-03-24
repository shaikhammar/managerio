import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, AlertCircle } from 'lucide-react';
import { useState, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BankReconciliation, BankTransaction, BreadcrumbItem } from '@/types';

type Props = {
    reconciliation: BankReconciliation;
    transactions: BankTransaction[];
};

export default function ReconciliationShow({ reconciliation, transactions }: Props) {
    const { format } = useCurrency();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Bank Reconciliations', href: '/banking/reconciliations' },
        { title: `As of ${reconciliation.statement_date}`, href: `/banking/reconciliations/${reconciliation.id}` },
    ];

    const [selectedIds, setSelectedIds] = useState<number[]>(
        transactions.filter(t => t.is_reconciled).map(t => t.id)
    );

    const reconciledSum = useMemo(() => {
        return transactions
            .filter(t => selectedIds.includes(t.id))
            .reduce((sum, t) => sum + parseFloat(t.amount.toString()), 0);
    }, [transactions, selectedIds]);

    // Note: In a real app, reconciled_balance would be (opening balance + reconciledSum)
    // For this simple version, we'll just check if the difference is zero based on statement vs ledger
    const currentDifference = parseFloat(reconciliation.statement_balance.toString()) - (parseFloat(reconciliation.reconciled_balance.toString()) + reconciledSum);

    const isBalanced = Math.abs(currentDifference) < 0.01;

    function toggleTransaction(id: number) {
        if (reconciliation.is_completed) {
return;
}

        setSelectedIds(prev => 
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    }

    function handleComplete() {
        if (confirm('Complete this reconciliation? Matched transactions will be marked as reconciled.')) {
            router.put(`/banking/reconciliations/${reconciliation.id}`, {
                action: 'complete',
                transaction_ids: selectedIds
            });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bank Reconciliation" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/banking/reconciliations">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Reconcile {reconciliation.bank_account?.name}</h1>
                            <p className="text-muted-foreground text-sm">Statement date: {reconciliation.statement_date}</p>
                        </div>
                    </div>
                    {!reconciliation.is_completed && (
                        <Button onClick={handleComplete} disabled={!isBalanced}>
                            Complete Reconciliation
                        </Button>
                    )}
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Statement Balance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{format(reconciliation.statement_balance)}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Reconciled Balance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{format(parseFloat(reconciliation.reconciled_balance.toString()) + reconciledSum)}</p>
                        </CardContent>
                    </Card>
                    <Card className={isBalanced ? 'bg-emerald-50 dark:bg-emerald-900/10' : 'bg-red-50 dark:bg-red-900/10'}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Difference</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <p className={`text-2xl font-bold ${isBalanced ? 'text-emerald-600' : 'text-red-600'}`}>
                                    {format(currentDifference)}
                                </p>
                                {isBalanced ? (
                                    <CheckCircle2 className="size-6 text-emerald-600" />
                                ) : (
                                    <AlertCircle className="size-6 text-red-600" />
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <div className="bg-muted/50 p-4 border-b">
                        <h3 className="font-semibold text-sm">Unreconciled Transactions</h3>
                        <p className="text-xs text-muted-foreground mt-1">Select transactions that appear on your bank statement</p>
                    </div>
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/20">
                                <th className="w-12 px-4 py-2 text-left"></th>
                                <th className="text-left py-2 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-2 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-right py-2 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="py-12 text-center text-muted-foreground">
                                        <CheckCircle2 className="size-8 mx-auto mb-2 opacity-20" />
                                        No unreconciled transactions for this period
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((tx) => (
                                    <tr 
                                        key={tx.id} 
                                        className={`border-b last:border-0 hover:bg-muted/30 transition-colors cursor-pointer ${selectedIds.includes(tx.id) ? 'bg-primary/5' : ''}`}
                                        onClick={() => toggleTransaction(tx.id)}
                                    >
                                        <td className="px-4 py-3">
                                            <input 
                                                type="checkbox" 
                                                checked={selectedIds.includes(tx.id)} 
                                                onChange={() => {}} // Handled by tr onClick
                                                disabled={reconciliation.is_completed}
                                                className="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4"
                                            />
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tx.date}</td>
                                        <td className="py-3 px-4 text-sm font-medium">{tx.description}</td>
                                        <td className={`py-3 px-4 text-right font-medium text-sm ${tx.amount > 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {tx.amount > 0 ? '+' : ''}{format(tx.amount)}
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

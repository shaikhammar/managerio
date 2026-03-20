import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Landmark, FileText, CheckCircle2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { Account, BankTransaction, BreadcrumbItem, PaginatedData } from '@/types';

type Props = {
    account: Account;
    balance: number;
    transactions: PaginatedData<BankTransaction>;
};

export default function BankAccountShow({ account, balance, transactions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Bank Accounts', href: '/banking/accounts' },
        { title: account.name, href: `/banking/accounts/${account.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${account.name} - Bank Account`} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/banking/accounts">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold tracking-tight">{account.name}</h1>
                                <span className="text-muted-foreground font-mono text-sm">{account.code}</span>
                            </div>
                            <p className="text-muted-foreground text-sm">Transaction history and reconciliation</p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-muted-foreground font-medium">Ledger Balance</p>
                        <p className={`text-3xl font-bold ${balance >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                            {formatCurrency(balance)}
                        </p>
                    </div>
                </div>

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Reference</th>
                                <th className="text-center py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No transactions found for this account</p>
                                    </td>
                                </tr>
                            ) : (
                                transactions.data.map((tx) => (
                                    <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tx.date}</td>
                                        <td className="py-3 px-4 text-sm">
                                            <p className="font-medium">{tx.description}</p>
                                            {tx.payment_id && (
                                                <Link
                                                    href={tx.amount > 0 ? `/payments/receipts/${tx.payment_id}` : `/payments/supplier-payments/${tx.payment_id}`}
                                                    className="text-[10px] text-blue-600 hover:underline"
                                                >
                                                    View Source Document
                                                </Link>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tx.reference || '—'}</td>
                                        <td className="py-3 px-4 text-center">
                                            {tx.is_reconciled ? (
                                                <div className="flex items-center justify-center gap-1 text-emerald-600">
                                                    <CheckCircle2 className="size-3" />
                                                    <span className="text-[10px] font-medium uppercase tracking-wider">Reconciled</span>
                                                </div>
                                            ) : (
                                                <div className="flex items-center justify-center gap-1 text-amber-600">
                                                    <XCircle className="size-3" />
                                                    <span className="text-[10px] font-medium uppercase tracking-wider">Unreconciled</span>
                                                </div>
                                            )}
                                        </td>
                                        <td className={`py-3 px-4 text-right font-medium text-sm ${tx.amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                            {tx.amount > 0 ? '+' : ''}{formatCurrency(tx.amount)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {transactions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {transactions.from} to {transactions.to} of {transactions.total}
                        </p>
                        <div className="flex gap-2">
                            {transactions.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={transactions.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {transactions.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={transactions.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, CheckCircle2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { Account, BankTransaction, BreadcrumbItem, JournalEntry, Payment } from '@/types';

type TransactionWithRelations = BankTransaction & {
    bank_account?: Account;
    payment?: Payment & { contact?: { name: string } };
    journal_entry?: JournalEntry & {
        lines?: Array<{ id: number; debit: number; credit: number; description: string | null; account?: Account }>;
    };
};

type Props = { transaction: TransactionWithRelations };

export default function BankTransactionShow({ transaction }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Bank Transactions', href: '/banking/transactions' },
        { title: transaction.description, href: `/banking/transactions/${transaction.id}` },
    ];

    const isManual = !transaction.payment_id && !transaction.journal_entry_id;

    function handleDelete() {
        if (confirm('Delete this transaction? This cannot be undone.')) {
            router.delete(`/banking/transactions/${transaction.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Transaction: ${transaction.description}`} />
            <div className="max-w-2xl mx-auto p-4 md:p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/banking/transactions"><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold">{transaction.description}</h1>
                            <p className="text-sm text-muted-foreground">{transaction.date} · {transaction.bank_account?.name}</p>
                        </div>
                    </div>
                    {isManual && !transaction.is_reconciled && (
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={`/banking/transactions/${transaction.id}/edit`}>
                                    <Pencil className="mr-2 size-4" />
                                    Edit
                                </Link>
                            </Button>
                            <Button variant="destructive" size="sm" onClick={handleDelete}>Delete</Button>
                        </div>
                    )}
                </div>

                <Card>
                    <CardHeader><CardTitle>Transaction Details</CardTitle></CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Amount</span>
                            <span className={`font-bold text-lg ${transaction.amount >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                {transaction.amount >= 0 ? '+' : ''}{formatCurrency(transaction.amount)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Bank Account</span>
                            <span>{transaction.bank_account?.name}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Date</span>
                            <span>{transaction.date}</span>
                        </div>
                        {transaction.reference && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Reference</span>
                                <span>{transaction.reference}</span>
                            </div>
                        )}
                        <div className="flex justify-between items-center">
                            <span className="text-muted-foreground">Status</span>
                            {transaction.is_reconciled ? (
                                <div className="flex items-center gap-1 text-emerald-600">
                                    <CheckCircle2 className="size-4" />
                                    <span>Reconciled</span>
                                </div>
                            ) : (
                                <div className="flex items-center gap-1 text-amber-600">
                                    <XCircle className="size-4" />
                                    <span>Unreconciled</span>
                                </div>
                            )}
                        </div>
                        {transaction.payment && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Linked To</span>
                                <span>{transaction.payment.contact?.name || 'Payment'}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {transaction.journal_entry && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Journal Entry</CardTitle>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={`/accounting/journal-entries/${transaction.journal_entry.id}`}>View</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-muted-foreground">
                                        <th className="text-left py-2">Account</th>
                                        <th className="text-left py-2">Description</th>
                                        <th className="text-right py-2">Debit</th>
                                        <th className="text-right py-2">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transaction.journal_entry.lines?.map((line) => (
                                        <tr key={line.id} className="border-b last:border-0">
                                            <td className="py-2">{line.account?.code} · {line.account?.name}</td>
                                            <td className="py-2 text-muted-foreground">{line.description}</td>
                                            <td className="py-2 text-right">{line.debit > 0 ? formatCurrency(line.debit) : '—'}</td>
                                            <td className="py-2 text-right">{line.credit > 0 ? formatCurrency(line.credit) : '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

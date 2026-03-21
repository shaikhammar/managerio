import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { Account, BreadcrumbItem, JournalEntryLine, PaginatedData } from '@/types';

type Props = {
    account: Account & { 
        parent?: Account | null;
        children?: Account[];
    };
    transactions: PaginatedData<JournalEntryLine>;
    balance: number;
};

export default function AccountShow({ account, transactions, balance }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Chart of Accounts', href: '/accounting/accounts' },
        { title: account.name, href: `/accounting/accounts/${account.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${account.name} - Account Ledger`} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/accounting/accounts">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold tracking-tight">{account.name}</h1>
                                <span className="text-muted-foreground font-mono text-sm">{account.code}</span>
                            </div>
                            <p className="text-muted-foreground text-sm uppercase tracking-wider text-[10px] font-bold">
                                {account.type} {account.sub_type && `· ${account.sub_type.replace(/_/g, ' ')}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-right mr-4 border-r pr-4 hidden sm:block">
                            <p className="text-xs text-muted-foreground font-medium uppercase">Current Balance</p>
                            <p className="text-xl font-bold">{formatCurrency(balance)}</p>
                        </div>
                        {!account.is_system && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={`/accounting/accounts/${account.id}/edit`}>
                                    <Edit className="mr-2 size-4" /> Edit Account
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {account.description && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">{account.description}</p>
                        </CardContent>
                    </Card>
                )}

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <div className="bg-muted/50 p-4 border-b">
                        <h3 className="font-semibold text-sm">Transaction Ledger</h3>
                    </div>
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/20">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Debit</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No transactions recorded for this account</p>
                                    </td>
                                </tr>
                            ) : (
                                transactions.data.map((line) => (
                                    <tr key={line.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 text-sm text-muted-foreground">
                                            {line.journal_entry?.date}
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            <p className="font-medium">{line.description || line.journal_entry?.description}</p>
                                            <Link 
                                                href={`/accounting/journal-entries/${line.journal_entry_id}`}
                                                className="text-[10px] text-blue-600 hover:underline"
                                            >
                                                {line.journal_entry?.entry_number}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-4 text-right text-sm">
                                            {line.debit > 0 ? formatCurrency(line.debit) : '—'}
                                        </td>
                                        <td className="py-3 px-4 text-right text-sm">
                                            {line.credit > 0 ? formatCurrency(line.credit) : '—'}
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

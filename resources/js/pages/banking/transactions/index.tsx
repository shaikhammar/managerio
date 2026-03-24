import { Head, Link, router } from '@inertiajs/react';
import { Search, FileText, CheckCircle2, XCircle, Landmark, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { Account, BankTransaction, BreadcrumbItem, PaginatedData } from '@/types';

type Props = {
    transactions: PaginatedData<BankTransaction>;
    filters: { search?: string; bank_account_id?: string };
    bankAccounts: Pick<Account, 'id' | 'name' | 'code'>[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Transactions', href: '/banking/transactions' },
];

export default function BankTransactionIndex({ transactions, filters, bankAccounts }: Props) {
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/banking/transactions', { ...filters, search }, { preserveState: true });
    }

    function handleAccountChange(id: string) {
        router.get('/banking/transactions', { ...filters, bank_account_id: id === 'all' ? undefined : id }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bank Transactions" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Bank Transactions</h1>
                        <p className="text-muted-foreground text-sm">All activity across your bank accounts</p>
                    </div>
                    <Button asChild>
                        <Link href="/banking/transactions/create"><Plus className="mr-2 size-4" /> New Transaction</Link>
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Search transactions..."
                                className="pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={filters.bank_account_id || 'all'} onValueChange={handleAccountChange}>
                        <SelectTrigger className="w-[220px]">
                            <SelectValue placeholder="All Accounts" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Accounts</SelectItem>
                            {bankAccounts.map((a) => (
                                <SelectItem key={a.id} value={a.id.toString()}>
                                    {a.name} ({a.code})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border overflow-hidden bg-white dark:bg-gray-950">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Bank Account</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-center py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <FileText className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No transactions found</p>
                                    </td>
                                </tr>
                            ) : (
                                transactions.data.map((tx) => (
                                    <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors cursor-pointer" onClick={() => router.visit(`/banking/transactions/${tx.id}`)}>
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tx.date}</td>
                                        <td className="py-3 px-4 text-sm font-medium">
                                            <div className="flex items-center gap-2">
                                                <Landmark className="size-3 text-muted-foreground" />
                                                {tx.bank_account?.name}
                                            </div>
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            <p className="font-medium">{tx.description}</p>
                                            {tx.payment && (
                                                <p className="text-[10px] text-muted-foreground">
                                                    Linked to {tx.payment.contact?.name || 'Contact'}
                                                </p>
                                            )}
                                        </td>
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
                                            {tx.amount > 0 ? '+' : ''}{format(tx.amount)}
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

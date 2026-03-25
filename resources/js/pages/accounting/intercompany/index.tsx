import { Head, Link } from '@inertiajs/react';
import { Plus, ArrowLeftRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, IntercompanyTransaction } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Intercompany Transactions', href: '/accounting/intercompany' },
];

type Props = {
    transactions: IntercompanyTransaction[];
};

export default function IntercompanyIndex({ transactions }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Intercompany Transactions" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Intercompany Transactions</h1>
                        <p className="text-muted-foreground text-sm">Transfer funds or charge between your businesses</p>
                    </div>
                    <Button asChild>
                        <Link href="/accounting/intercompany/create">
                            <Plus className="mr-2 size-4" /> New Transfer
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Description</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">From</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">To</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center">
                                        <ArrowLeftRight className="size-10 mx-auto mb-2 text-muted-foreground/30" />
                                        <p className="text-muted-foreground">No intercompany transactions yet</p>
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((tx) => (
                                    <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 text-sm text-muted-foreground">{tx.date}</td>
                                        <td className="py-3 px-4">
                                            <Link
                                                href={`/accounting/intercompany/${tx.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {tx.description}
                                            </Link>
                                            {tx.reference && (
                                                <p className="text-xs text-muted-foreground mt-0.5">{tx.reference}</p>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            <div className="font-medium">{tx.source_business?.name ?? '—'}</div>
                                            {tx.source_account && (
                                                <div className="text-xs text-muted-foreground">{tx.source_account.code} · {tx.source_account.name}</div>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm">
                                            <div className="font-medium">{tx.target_business?.name ?? '—'}</div>
                                            {tx.target_account && (
                                                <div className="text-xs text-muted-foreground">{tx.target_account.code} · {tx.target_account.name}</div>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-right font-medium tabular-nums">
                                            {Number(tx.amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
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

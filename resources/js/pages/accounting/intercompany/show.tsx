import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, IntercompanyTransaction } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Intercompany Transactions', href: '/accounting/intercompany' },
    { title: 'Transfer Details', href: '#' },
];

type Props = {
    transaction: IntercompanyTransaction;
};

export default function IntercompanyShow({ transaction }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Intercompany Transfer" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-2xl">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Intercompany Transfer</h1>
                        <p className="text-muted-foreground text-sm mt-1">{transaction.date}</p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/accounting/intercompany">
                            <ArrowLeft className="mr-2 size-4" /> Back
                        </Link>
                    </Button>
                </div>

                {/* Details Card */}
                <div className="rounded-lg border p-5 flex flex-col gap-4">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p className="text-muted-foreground text-xs uppercase tracking-wide font-medium mb-1">Amount</p>
                            <p className="text-xl font-bold tabular-nums">
                                {Number(transaction.amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                            </p>
                        </div>
                        {transaction.reference && (
                            <div>
                                <p className="text-muted-foreground text-xs uppercase tracking-wide font-medium mb-1">Reference</p>
                                <p className="font-medium">{transaction.reference}</p>
                            </div>
                        )}
                    </div>

                    <div>
                        <p className="text-muted-foreground text-xs uppercase tracking-wide font-medium mb-1">Description</p>
                        <p>{transaction.description}</p>
                    </div>

                    <hr />

                    {/* From → To */}
                    <div className="flex items-center gap-4">
                        <div className="flex-1 rounded-md bg-muted/50 p-3">
                            <p className="text-xs text-muted-foreground uppercase tracking-wide font-medium mb-1">From</p>
                            <p className="font-semibold">{transaction.source_business?.name}</p>
                            {transaction.source_account && (
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    {transaction.source_account.code} · {transaction.source_account.name}
                                </p>
                            )}
                            {transaction.source_journal_entry && (
                                <Link
                                    href={`/accounting/journal-entries/${transaction.source_journal_entry.id}`}
                                    className="text-xs text-primary hover:underline mt-1 inline-block"
                                >
                                    {transaction.source_journal_entry.entry_number}
                                </Link>
                            )}
                        </div>

                        <ArrowRight className="size-5 text-muted-foreground shrink-0" />

                        <div className="flex-1 rounded-md bg-muted/50 p-3">
                            <p className="text-xs text-muted-foreground uppercase tracking-wide font-medium mb-1">To</p>
                            <p className="font-semibold">{transaction.target_business?.name}</p>
                            {transaction.target_account && (
                                <p className="text-sm text-muted-foreground mt-0.5">
                                    {transaction.target_account.code} · {transaction.target_account.name}
                                </p>
                            )}
                            {transaction.target_journal_entry && (
                                <Link
                                    href={`/accounting/journal-entries/${transaction.target_journal_entry.id}`}
                                    className="text-xs text-primary hover:underline mt-1 inline-block"
                                >
                                    {transaction.target_journal_entry.entry_number}
                                </Link>
                            )}
                        </div>
                    </div>

                    {transaction.creator && (
                        <p className="text-xs text-muted-foreground">
                            Recorded by {transaction.creator.name}
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

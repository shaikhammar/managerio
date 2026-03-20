import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Landmark, User, FileText, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { Payment, BreadcrumbItem } from '@/types';

type Props = {
    receipt: Payment;
};

export default function ReceiptShow({ receipt }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Receipts', href: '/payments/receipts' },
        { title: receipt.number, href: `/payments/receipts/${receipt.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Receipt ${receipt.number}`} />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/payments/receipts">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{receipt.number}</h1>
                                <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    Received
                                </span>
                            </div>
                            <p className="text-muted-foreground text-sm">{receipt.date}</p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-xs text-muted-foreground font-medium uppercase">Amount Received</p>
                        <p className="text-3xl font-bold text-emerald-600">{formatCurrency(receipt.amount)}</p>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Customer Details</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-3">
                            <div className="rounded-full bg-muted p-2">
                                <User className="size-4 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-semibold">{receipt.contact?.name}</p>
                                <p className="text-xs text-muted-foreground">{receipt.contact?.email}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Payment Method</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-3">
                            <div className="rounded-full bg-blue-50 dark:bg-blue-900/20 p-2">
                                <Landmark className="size-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="font-semibold">{receipt.bank_account?.name}</p>
                                <p className="text-xs text-muted-foreground font-mono">{receipt.bank_account?.code}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {receipt.description && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">{receipt.description}</p>
                        </CardContent>
                    </Card>
                )}

                {receipt.allocations && receipt.allocations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Invoices Paid</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b bg-muted/30 text-[10px] uppercase tracking-wider text-muted-foreground">
                                        <th className="text-left py-2 px-4 font-semibold">Invoice Number</th>
                                        <th className="text-right py-2 px-4 font-semibold">Amount Allocated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {receipt.allocations.map((alloc) => (
                                        <tr key={alloc.id} className="border-b last:border-0">
                                            <td className="py-3 px-4 text-sm font-mono">
                                                <Link href={`/sales/invoices/${alloc.invoice_id}`} className="text-blue-600 hover:underline">
                                                    {alloc.invoice?.number}
                                                </Link>
                                            </td>
                                            <td className="py-3 px-4 text-right text-sm font-medium">
                                                {formatCurrency(alloc.amount)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                )}

                <div className="flex justify-center pt-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/accounting/journal-entries/${receipt.journal_entry_id}`}>
                            <FileText className="mr-2 size-4" /> View Accounting Entry
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Landmark, User, FileText, ShoppingCart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import type { Payment, BreadcrumbItem } from '@/types';

type Props = {
    payment: Payment;
};

export default function SupplierPaymentShow({ payment }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Supplier Payments', href: '/payments/supplier-payments' },
        { title: payment.number, href: `/payments/supplier-payments/${payment.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Payment ${payment.number}`} />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/payments/supplier-payments">
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{payment.number}</h1>
                                <span className="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    Paid
                                </span>
                            </div>
                            <p className="text-muted-foreground text-sm">{payment.date}</p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-xs text-muted-foreground font-medium uppercase">Amount Paid</p>
                        <p className="text-3xl font-bold text-red-600">-{formatCurrency(payment.amount)}</p>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Supplier Details</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-3">
                            <div className="rounded-full bg-muted p-2">
                                <User className="size-4 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-semibold">{payment.contact?.name}</p>
                                <p className="text-xs text-muted-foreground">{payment.contact?.email}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Paid From</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-3">
                            <div className="rounded-full bg-blue-50 dark:bg-blue-900/20 p-2">
                                <Landmark className="size-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="font-semibold">{payment.bank_account?.name}</p>
                                <p className="text-xs text-muted-foreground font-mono">{payment.bank_account?.code}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {payment.description && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">{payment.description}</p>
                        </CardContent>
                    </Card>
                )}

                {payment.allocations && payment.allocations.length > 0 && (
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
                                    {payment.allocations.map((alloc) => (
                                        <tr key={alloc.id} className="border-b last:border-0">
                                            <td className="py-3 px-4 text-sm font-mono">
                                                <Link href={`/purchases/invoices/${alloc.invoice_id}`} className="text-blue-600 hover:underline">
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
                        <Link href={`/accounting/journal-entries/${payment.journal_entry_id}`}>
                            <FileText className="mr-2 size-4" /> View Accounting Entry
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

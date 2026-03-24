import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Send, Ban, CreditCard } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BreadcrumbItem, Invoice } from '@/types';

type Props = { invoice: Invoice };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    partially_paid: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    overdue: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    void: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

export default function InvoiceShow({ invoice }: Props) {
    const { format } = useCurrency();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Invoices', href: '/sales/invoices' },
        { title: invoice.number, href: `/sales/invoices/${invoice.id}` },
    ];

    const canEdit = invoice.status === 'draft';
    const canVoid = ['sent', 'overdue'].includes(invoice.status);

    function handlePost() {
        router.post(`/sales/invoices/${invoice.id}/post`);
    }
    function handleVoid() {
        if (confirm('Are you sure you want to void this invoice?')) {
            router.post(`/sales/invoices/${invoice.id}/void`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Invoice ${invoice.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/sales/invoices"><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{invoice.number}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[invoice.status] || ''}`}>
                                    {invoice.status.replace('_', ' ')}
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {invoice.contact?.name || 'No customer'} · {invoice.date}
                                {invoice.due_date && ` · Due ${invoice.due_date}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={`/sales/invoices/${invoice.id}/edit`}>Edit</Link>
                                </Button>
                                <Button onClick={handlePost}>
                                    <Send className="mr-2 size-4" />
                                    Post
                                </Button>
                            </>
                        )}
                        {canVoid && (
                            <Button variant="destructive" onClick={handleVoid}>
                                <Ban className="mr-2 size-4" />
                                Void
                            </Button>
                        )}
                        {invoice.balance_due > 0 && invoice.status !== 'void' && invoice.status !== 'draft' && (
                            <Button variant="outline" asChild>
                                <Link href={`/payments/receipts/create?invoice_id=${invoice.id}`}>
                                    <CreditCard className="mr-2 size-4" />
                                    Record Payment
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Line Items */}
                <Card>
                    <CardContent className="pt-6">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b text-sm text-muted-foreground">
                                    <th className="text-left py-2">Description</th>
                                    <th className="text-right py-2 w-20">Qty</th>
                                    <th className="text-right py-2 w-28">Unit Price</th>
                                    <th className="text-right py-2 w-20">Disc %</th>
                                    <th className="text-left py-2 w-24">Tax</th>
                                    <th className="text-right py-2 w-28">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoice.lines?.map((line) => (
                                    <tr key={line.id} className="border-b last:border-0">
                                        <td className="py-3">
                                            <p className="font-medium text-sm">{line.description}</p>
                                            {line.account && (
                                                <p className="text-xs text-muted-foreground">{line.account.code} · {line.account.name}</p>
                                            )}
                                        </td>
                                        <td className="py-3 text-right text-sm">{line.quantity}</td>
                                        <td className="py-3 text-right text-sm">{format(line.unit_price)}</td>
                                        <td className="py-3 text-right text-sm">{line.discount_percent > 0 ? `${line.discount_percent}%` : '—'}</td>
                                        <td className="py-3 text-sm">{line.tax_code?.name || '—'}</td>
                                        <td className="py-3 text-right text-sm font-medium">{format(line.line_total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex justify-end mt-6">
                            <div className="w-64 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{format(invoice.subtotal)}</span>
                                </div>
                                {invoice.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax</span>
                                        <span>{format(invoice.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>Total</span>
                                    <span>{format(invoice.total)}</span>
                                </div>
                                {invoice.amount_paid > 0 && (
                                    <>
                                        <div className="flex justify-between text-sm text-emerald-600">
                                            <span>Paid</span>
                                            <span>-{format(invoice.amount_paid)}</span>
                                        </div>
                                        <div className="flex justify-between font-bold border-t pt-1 text-amber-600">
                                            <span>Balance Due</span>
                                            <span>{format(invoice.balance_due)}</span>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes & Terms */}
                {(invoice.notes || invoice.terms) && (
                    <div className="grid grid-cols-2 gap-6">
                        {invoice.notes && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Notes</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{invoice.notes}</p></CardContent>
                            </Card>
                        )}
                        {invoice.terms && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Terms & Conditions</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{invoice.terms}</p></CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CreditCard } from 'lucide-react';
import SupplierPaymentController from '@/actions/App/Http/Controllers/Payments/SupplierPaymentController';
import PurchaseInvoiceController from '@/actions/App/Http/Controllers/Purchases/PurchaseInvoiceController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import { formatCurrency } from '@/lib/utils';
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

export default function PurchaseInvoiceShow({ invoice }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Purchase Invoices', href: PurchaseInvoiceController.index.url() },
        { title: invoice.number, href: PurchaseInvoiceController.show.url(invoice) },
    ];

    const { currency: baseCurrency } = useCurrency();
    const isForeignCurrency = invoice.currency_code !== baseCurrency;

    const canEdit = !['paid', 'partially_paid', 'void'].includes(invoice.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Purchase Invoice ${invoice.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={PurchaseInvoiceController.index.url()}><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{invoice.number}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[invoice.status] || ''}`}>
                                    {invoice.status.replace('_', ' ')}
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {invoice.contact?.name || 'No supplier'} · {invoice.date}
                                {invoice.due_date && ` · Due ${invoice.due_date}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Button variant="outline" asChild>
                                <Link href={PurchaseInvoiceController.edit.url(invoice)}>Edit</Link>
                            </Button>
                        )}
                        {invoice.balance_due > 0 && invoice.status !== 'void' && (
                            <Button variant="outline" asChild>
                                <Link href={SupplierPaymentController.create.url({ mergeQuery: { invoice_id: invoice.id } })}>
                                    <CreditCard className="mr-2 size-4" />
                                    Make Payment
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
                                        <td className="py-3 text-right text-sm">{formatCurrency(line.unit_price, invoice.currency_code)}</td>
                                        <td className="py-3 text-right text-sm">{line.discount_percent > 0 ? `${line.discount_percent}%` : '—'}</td>
                                        <td className="py-3 text-sm">{line.tax_code?.name || '—'}</td>
                                        <td className="py-3 text-right text-sm font-medium">{formatCurrency(line.line_total, invoice.currency_code)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex justify-end mt-6">
                            <div className="w-64 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatCurrency(invoice.subtotal, invoice.currency_code)}</span>
                                </div>
                                {invoice.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax</span>
                                        <span>{formatCurrency(invoice.tax_amount, invoice.currency_code)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>Total</span>
                                    <span>{formatCurrency(invoice.total, invoice.currency_code)}</span>
                                </div>
                                {isForeignCurrency && (
                                    <div className="flex justify-between text-xs text-muted-foreground">
                                        <span>≈ {baseCurrency} equivalent</span>
                                        <span>{formatCurrency(invoice.total * invoice.exchange_rate, baseCurrency)}</span>
                                    </div>
                                )}
                                {invoice.amount_paid > 0 && (
                                    <>
                                        <div className="flex justify-between text-sm text-red-600">
                                            <span>Paid</span>
                                            <span>-{formatCurrency(invoice.amount_paid, invoice.currency_code)}</span>
                                        </div>
                                        <div className="flex justify-between font-bold border-t pt-1 text-amber-600">
                                            <span>Balance Due</span>
                                            <span>{formatCurrency(invoice.balance_due, invoice.currency_code)}</span>
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

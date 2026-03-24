import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, Send, FileCheck } from 'lucide-react';
import PurchaseOrderController from '@/actions/App/Http/Controllers/Purchases/PurchaseOrderController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { useCurrency } from '@/hooks/use-currency';
import type { BreadcrumbItem, Invoice } from '@/types';

type Props = { purchaseOrder: Invoice & { purchase_invoices?: Invoice[] } };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    partially_received: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    received: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    invoiced: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
};

export default function PurchaseOrderShow({ purchaseOrder }: Props) {
    const { format } = useCurrency();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Purchase Orders', href: PurchaseOrderController.index.url() },
        { title: purchaseOrder.number, href: PurchaseOrderController.show.url(purchaseOrder) },
    ];

    const canEdit = purchaseOrder.status === 'draft';
    const canSend = purchaseOrder.status === 'draft';
    const canConvert = purchaseOrder.status !== 'invoiced' && purchaseOrder.status !== 'cancelled';

    function handleSend() {
        router.post(PurchaseOrderController.send.url(purchaseOrder));
    }

    function handleConvert() {
        router.post(PurchaseOrderController.convert.url(purchaseOrder));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Purchase Order ${purchaseOrder.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={PurchaseOrderController.index.url()}><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{purchaseOrder.number}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[purchaseOrder.status] || ''}`}>
                                    {purchaseOrder.status.replace(/_/g, ' ')}
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {purchaseOrder.contact?.name || 'No supplier'} · {purchaseOrder.date}
                                {purchaseOrder.due_date && ` · Expected ${purchaseOrder.due_date}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={PurchaseOrderController.pdf.url(purchaseOrder.id)} target="_blank" rel="noreferrer">
                                <Download className="mr-2 size-4" />
                                PDF
                            </a>
                        </Button>
                        {canEdit && (
                            <Button variant="outline" asChild>
                                <Link href={PurchaseOrderController.edit.url(purchaseOrder)}>Edit</Link>
                            </Button>
                        )}
                        {canSend && (
                            <Button variant="outline" onClick={handleSend}>
                                <Send className="mr-2 size-4" />
                                Mark as Sent
                            </Button>
                        )}
                        {canConvert && (
                            <Button onClick={handleConvert}>
                                <FileCheck className="mr-2 size-4" />
                                Convert to Invoice
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
                                {purchaseOrder.lines?.map((line) => (
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
                                    <span>{format(purchaseOrder.subtotal)}</span>
                                </div>
                                {purchaseOrder.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax</span>
                                        <span>{format(purchaseOrder.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>Total</span>
                                    <span>{format(purchaseOrder.total)}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes & Terms */}
                {(purchaseOrder.notes || purchaseOrder.terms) && (
                    <div className="grid grid-cols-2 gap-6">
                        {purchaseOrder.notes && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Notes</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{purchaseOrder.notes}</p></CardContent>
                            </Card>
                        )}
                        {purchaseOrder.terms && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Terms & Conditions</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{purchaseOrder.terms}</p></CardContent>
                            </Card>
                        )}
                    </div>
                )}

                {/* Linked Purchase Invoices */}
                {purchaseOrder.purchase_invoices && purchaseOrder.purchase_invoices.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Linked Purchase Invoice</CardTitle></CardHeader>
                        <CardContent>
                            {purchaseOrder.purchase_invoices.map((inv) => (
                                <div key={inv.id} className="flex items-center justify-between py-2">
                                    <Link href={`/purchases/invoices/${inv.id}`} className="font-mono text-sm font-medium hover:underline">
                                        {inv.number}
                                    </Link>
                                    <span className="text-sm text-muted-foreground">{format(inv.total)}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

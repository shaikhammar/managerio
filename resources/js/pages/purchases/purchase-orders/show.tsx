import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, Download, FileCheck, PackageCheck, Play, Send } from 'lucide-react';
import PurchaseInvoiceController from '@/actions/App/Http/Controllers/Purchases/PurchaseInvoiceController';
import PurchaseOrderController from '@/actions/App/Http/Controllers/Purchases/PurchaseOrderController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/hooks/use-currency';
import { formatCurrency } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice } from '@/types';

type Props = { purchaseOrder: Invoice & { purchase_invoices?: Invoice[] } };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    accepted: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    in_progress: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
    delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    partially_received: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    received: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    invoiced: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
};

export default function PurchaseOrderShow({ purchaseOrder }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Purchase Orders', href: PurchaseOrderController.index.url() },
        { title: purchaseOrder.number, href: PurchaseOrderController.show.url(purchaseOrder.id) },
    ];

    const { currency: baseCurrency } = useCurrency();
    const isForeignCurrency = purchaseOrder.currency_code !== baseCurrency;

    const canEdit = purchaseOrder.status === 'draft';
    const canSend = purchaseOrder.status === 'draft';
    const canAccept = purchaseOrder.status === 'sent';
    const canStart = purchaseOrder.status === 'accepted';
    const canDeliver = purchaseOrder.status === 'in_progress';
    const canConvert = purchaseOrder.status !== 'invoiced' && purchaseOrder.status !== 'cancelled';

    function handleSend() {
        router.post(PurchaseOrderController.send.url(purchaseOrder.id));
    }

    function handleAccept() {
        router.post(PurchaseOrderController.accept.url(purchaseOrder.id));
    }

    function handleStart() {
        router.post(PurchaseOrderController.startProgress.url(purchaseOrder.id));
    }

    function handleDeliver() {
        router.post(PurchaseOrderController.deliver.url(purchaseOrder.id));
    }

    function handleConvert() {
        router.post(PurchaseOrderController.convert.url(purchaseOrder.id));
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
                                <Link href={PurchaseOrderController.edit.url(purchaseOrder.id)}>Edit</Link>
                            </Button>
                        )}
                        {canSend && (
                            <Button variant="outline" onClick={handleSend}>
                                <Send className="mr-2 size-4" />
                                Mark as Sent
                            </Button>
                        )}
                        {canAccept && (
                            <Button variant="outline" onClick={handleAccept}>
                                <CheckCircle className="mr-2 size-4" />
                                Accept
                            </Button>
                        )}
                        {canStart && (
                            <Button variant="outline" onClick={handleStart}>
                                <Play className="mr-2 size-4" />
                                Start Work
                            </Button>
                        )}
                        {canDeliver && (
                            <Button variant="outline" onClick={handleDeliver}>
                                <PackageCheck className="mr-2 size-4" />
                                Mark Delivered
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
                                            {line.language_pair && (
                                                <p className="text-xs text-muted-foreground">
                                                    {line.language_pair.sourceLanguage?.name} → {line.language_pair.targetLanguage?.name}
                                                    {line.service_type && ` · ${line.service_type.name}`}
                                                    {line.billing_unit && ` (${line.billing_unit})`}
                                                </p>
                                            )}
                                        </td>
                                        <td className="py-3 text-right text-sm">{line.quantity}</td>
                                        <td className="py-3 text-right text-sm">{formatCurrency(line.unit_price, purchaseOrder.currency_code)}</td>
                                        <td className="py-3 text-right text-sm">{line.discount_percent > 0 ? `${line.discount_percent}%` : '—'}</td>
                                        <td className="py-3 text-sm">{line.tax_code?.name || '—'}</td>
                                        <td className="py-3 text-right text-sm font-medium">{formatCurrency(line.line_total, purchaseOrder.currency_code)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex justify-end mt-6">
                            <div className="w-64 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatCurrency(purchaseOrder.subtotal, purchaseOrder.currency_code)}</span>
                                </div>
                                {purchaseOrder.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax</span>
                                        <span>{formatCurrency(purchaseOrder.tax_amount, purchaseOrder.currency_code)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>Total</span>
                                    <span>{formatCurrency(purchaseOrder.total, purchaseOrder.currency_code)}</span>
                                </div>
                                {isForeignCurrency && (
                                    <div className="flex justify-between text-xs text-muted-foreground">
                                        <span>≈ {baseCurrency} equivalent</span>
                                        <span>{formatCurrency(purchaseOrder.total * purchaseOrder.exchange_rate, baseCurrency)}</span>
                                    </div>
                                )}
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
                                    <Link href={PurchaseInvoiceController.show.url(inv)} className="font-mono text-sm font-medium hover:underline">
                                        {inv.number}
                                    </Link>
                                    <span className="text-sm text-muted-foreground">{formatCurrency(inv.total, inv.currency_code)}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

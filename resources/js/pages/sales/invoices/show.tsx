import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Ban, CreditCard, Download, Link2, Mail, Send } from 'lucide-react';
import { useState } from 'react';
import ReceiptController from '@/actions/App/Http/Controllers/Payments/ReceiptController';
import InvoiceController from '@/actions/App/Http/Controllers/Sales/InvoiceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

export default function InvoiceShow({ invoice }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Invoices', href: InvoiceController.index.url() },
        { title: invoice.number, href: InvoiceController.show.url(invoice) },
    ];

    const { currency: baseCurrency } = useCurrency();
    const isForeignCurrency = invoice.currency_code !== baseCurrency;

    const canEdit = !['paid', 'partially_paid', 'void'].includes(invoice.status);
    const canVoid = ['sent', 'overdue'].includes(invoice.status);

    const emailForm = useForm({ email: invoice.contact?.email ?? '' });
    const [emailOpen, setEmailOpen] = useState(false);
    const [copying, setCopying] = useState(false);

    async function copyPortalLink() {
        setCopying(true);

        try {
            const res = await fetch(InvoiceController.portalLink.url(invoice));
            const data = (await res.json()) as { url: string };
            await navigator.clipboard.writeText(data.url);
        } finally {
            setCopying(false);
        }
    }

    function handlePost() {
        router.post(InvoiceController.post.url(invoice));
    }
    function handleVoid() {
        if (confirm('Are you sure you want to void this invoice?')) {
            router.post(InvoiceController.voidMethod.url(invoice));
        }
    }
    function handleSendEmail(e: React.FormEvent) {
        e.preventDefault();
        emailForm.post(InvoiceController.sendEmail.url(invoice), {
            onSuccess: () => setEmailOpen(false),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Invoice ${invoice.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={InvoiceController.index.url()}><ArrowLeft className="size-4" /></Link>
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
                        <Button variant="outline" size="sm" onClick={copyPortalLink} disabled={copying}>
                            <Link2 className="size-4 mr-2" />
                            {copying ? 'Copying…' : 'Copy Portal Link'}
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={InvoiceController.pdf.url(invoice)} target="_blank" rel="noreferrer">
                                <Download className="mr-2 size-4" />
                                PDF
                            </a>
                        </Button>
                        {invoice.status !== 'void' && invoice.status !== 'draft' && (
                            <Button variant="outline" size="sm" onClick={() => setEmailOpen(true)}>
                                <Mail className="mr-2 size-4" />
                                Send Email
                            </Button>
                        )}
                        {canEdit && (
                            <Button variant="outline" asChild>
                                <Link href={InvoiceController.edit.url(invoice)}>Edit</Link>
                            </Button>
                        )}
                        {invoice.status === 'draft' && (
                            <Button onClick={handlePost}>
                                <Send className="mr-2 size-4" />
                                Post
                            </Button>
                        )}
                        {canVoid && (
                            <Button variant="destructive" onClick={handleVoid}>
                                <Ban className="mr-2 size-4" />
                                Void
                            </Button>
                        )}
                        {invoice.balance_due > 0 && invoice.status !== 'void' && invoice.status !== 'draft' && (
                            <Button variant="outline" asChild>
                                <Link href={ReceiptController.create.url({ mergeQuery: { invoice_id: invoice.id } })}>
                                    <CreditCard className="mr-2 size-4" />
                                    Record Payment
                                </Link>
                            </Button>
                        )}
                    </div>

                    <Dialog open={emailOpen} onOpenChange={setEmailOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Send Invoice {invoice.number}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleSendEmail} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Recipient Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={emailForm.data.email}
                                        onChange={(e) => emailForm.setData('email', e.target.value)}
                                        placeholder="customer@example.com"
                                        autoFocus
                                    />
                                    <InputError message={emailForm.errors.email} />
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setEmailOpen(false)}>Cancel</Button>
                                    <Button type="submit" disabled={emailForm.processing}>
                                        <Mail className="mr-2 size-4" />
                                        {emailForm.processing ? 'Sending…' : 'Send'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
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
                                        <div className="flex justify-between text-sm text-emerald-600">
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

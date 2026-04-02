import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Ban, Download, FileCheck, Link2, Mail } from 'lucide-react';
import { useState } from 'react';
import QuoteController from '@/actions/App/Http/Controllers/Sales/QuoteController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice } from '@/types';

type Props = { quote: Invoice };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

export default function QuoteShow({ quote }: Props) {
    const { format } = useCurrency();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Quotes', href: QuoteController.index.url() },
        { title: quote.number, href: QuoteController.show.url(quote) },
    ];

    const isDraft = quote.status === 'draft';
    const isConverted = quote.status === 'approved';

    const emailForm = useForm({ email: quote.contact?.email ?? '' });
    const [emailOpen, setEmailOpen] = useState(false);
    const [copying, setCopying] = useState(false);

    async function copyPortalLink() {
        setCopying(true);
        try {
            const res = await fetch(QuoteController.portalLink.url(quote));
            const data = (await res.json()) as { url: string };
            await navigator.clipboard.writeText(data.url);
        } finally {
            setCopying(false);
        }
    }

    function handleConvert() {
        if (confirm('Convert this quote into a sales invoice? This will generate accounting entries.')) {
            router.post(QuoteController.convert.url(quote));
        }
    }
    function handleSendEmail(e: React.FormEvent) {
        e.preventDefault();
        emailForm.post(QuoteController.sendEmail.url(quote), {
            onSuccess: () => setEmailOpen(false),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Quote ${quote.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={QuoteController.index.url()}><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{quote.number}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[quote.status] || ''}`}>
                                    {isConverted ? 'Converted' : quote.status.replace('_', ' ')}
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {quote.contact?.name || 'No customer'} · {quote.date}
                                {quote.due_date && ` · Expiry ${quote.due_date}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={copyPortalLink} disabled={copying}>
                            <Link2 className="size-4 mr-2" />
                            {copying ? 'Copying…' : 'Copy Portal Link'}
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={QuoteController.pdf.url(quote)} target="_blank" rel="noreferrer">
                                <Download className="mr-2 size-4" />
                                PDF
                            </a>
                        </Button>
                        {quote.status !== 'cancelled' && (
                            <Button variant="outline" size="sm" onClick={() => setEmailOpen(true)}>
                                <Mail className="mr-2 size-4" />
                                Send Email
                            </Button>
                        )}
                        {isDraft && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={QuoteController.edit.url(quote)}>Edit</Link>
                                </Button>
                                <Button onClick={handleConvert}>
                                    <FileCheck className="mr-2 size-4" />
                                    Convert to Invoice
                                </Button>
                            </>
                        )}
                        {!isConverted && quote.status !== 'cancelled' && (
                            <Button variant="ghost" className="text-muted-foreground hover:text-red-600">
                                <Ban className="mr-2 size-4" />
                                Cancel Quote
                            </Button>
                        )}
                    </div>

                    <Dialog open={emailOpen} onOpenChange={setEmailOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Send Quote {quote.number}</DialogTitle>
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
                                {quote.lines?.map((line) => (
                                    <tr key={line.id} className="border-b last:border-0">
                                        <td className="py-3">
                                            <p className="font-medium text-sm">{line.description}</p>
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
                                    <span>{format(quote.subtotal)}</span>
                                </div>
                                {quote.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax</span>
                                        <span>{format(quote.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2">
                                    <span>Total</span>
                                    <span>{format(quote.total)}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes & Terms */}
                {(quote.notes || quote.terms) && (
                    <div className="grid grid-cols-2 gap-6">
                        {quote.notes && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Notes</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{quote.notes}</p></CardContent>
                            </Card>
                        )}
                        {quote.terms && (
                            <Card>
                                <CardHeader><CardTitle className="text-sm">Terms & Conditions</CardTitle></CardHeader>
                                <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{quote.terms}</p></CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

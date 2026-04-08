import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Printer } from 'lucide-react';
import CreditNoteController from '@/actions/App/Http/Controllers/Sales/CreditNoteController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/hooks/use-currency';
import { formatCurrency } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Invoice } from '@/types';

type Props = { creditNote: Invoice };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    sent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    void: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
};

export default function CreditNoteShow({ creditNote }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Credit Notes', href: CreditNoteController.index.url() },
        { title: creditNote.number, href: CreditNoteController.show.url(creditNote.id) },
    ];

    const { currency: baseCurrency } = useCurrency();
    const isForeignCurrency = creditNote.currency_code !== baseCurrency;

    const canEdit = creditNote.status !== 'void';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Credit Note ${creditNote.number}`} />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={CreditNoteController.index.url()}><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold font-mono">{creditNote.number}</h1>
                                <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[creditNote.status] || ''}`}>
                                    {creditNote.status === 'sent' ? 'Posted' : creditNote.status.replace('_', ' ')}
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {creditNote.contact?.name || 'No customer'} · {creditNote.date}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={CreditNoteController.pdf.url(creditNote.id)} target="_blank" rel="noreferrer">
                                <Download className="mr-2 size-4" />
                                PDF
                            </a>
                        </Button>
                        {canEdit && (
                            <Button variant="outline" asChild>
                                <Link href={CreditNoteController.edit.url(creditNote.id)}>Edit</Link>
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => window.print()}>
                            <Printer className="mr-2 size-4" />
                            Print
                        </Button>
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
                                    <th className="text-right py-2 w-28">Total Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                {creditNote.lines?.map((line) => (
                                    <tr key={line.id} className="border-b last:border-0">
                                        <td className="py-3">
                                            <p className="font-medium text-sm">{line.description}</p>
                                            {line.account && (
                                                <p className="text-xs text-muted-foreground">{line.account.code} · {line.account.name}</p>
                                            )}
                                        </td>
                                        <td className="py-3 text-right text-sm">{line.quantity}</td>
                                        <td className="py-3 text-right text-sm">{formatCurrency(line.unit_price, creditNote.currency_code)}</td>
                                        <td className="py-3 text-right text-sm">{line.discount_percent > 0 ? `${line.discount_percent}%` : '—'}</td>
                                        <td className="py-3 text-sm">{line.tax_code?.name || '—'}</td>
                                        <td className="py-3 text-right text-sm font-medium">{formatCurrency(line.line_total, creditNote.currency_code)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex justify-end mt-6">
                            <div className="w-64 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal Credit</span>
                                    <span>{formatCurrency(creditNote.subtotal, creditNote.currency_code)}</span>
                                </div>
                                {creditNote.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Tax Credit</span>
                                        <span>{formatCurrency(creditNote.tax_amount, creditNote.currency_code)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg border-t pt-2 text-red-600 dark:text-red-400">
                                    <span>Total Credit</span>
                                    <span>{formatCurrency(creditNote.total, creditNote.currency_code)}</span>
                                </div>
                                {isForeignCurrency && (
                                    <div className="flex justify-between text-xs text-muted-foreground">
                                        <span>≈ {baseCurrency} equivalent</span>
                                        <span>{formatCurrency(creditNote.total * creditNote.exchange_rate, baseCurrency)}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes */}
                {creditNote.notes && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Notes</CardTitle></CardHeader>
                        <CardContent><p className="text-sm text-muted-foreground whitespace-pre-wrap">{creditNote.notes}</p></CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

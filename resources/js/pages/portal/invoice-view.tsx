import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PortalLayout from '@/layouts/portal-layout';
import { formatCurrency } from '@/lib/utils';
import type { Business, Invoice } from '@/types';

type Props = {
    invoice: Invoice;
    business: Business;
    isVoid: boolean;
    pdfUrl: string;
};

const statusColors: Record<string, string> = {
    sent: 'bg-blue-100 text-blue-700',
    paid: 'bg-emerald-100 text-emerald-700',
    partially_paid: 'bg-amber-100 text-amber-700',
    overdue: 'bg-red-100 text-red-700',
    void: 'bg-gray-100 text-gray-500',
};

export default function InvoiceView({ invoice, business, isVoid, pdfUrl }: Props) {

    return (
        <PortalLayout businessName={business.name} logoPath={business.logo_path}>
            <Head title={`Invoice ${invoice.number}`} />
            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Invoice {invoice.number}</h1>
                        <p className="text-gray-500 text-sm mt-1">
                            Issued {invoice.date}
                            {invoice.due_date && ` · Due ${invoice.due_date}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <span
                            className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[invoice.status] ?? 'bg-gray-100 text-gray-600'}`}
                        >
                            {invoice.status.replace('_', ' ')}
                        </span>
                        {!isVoid && (
                            <Button asChild variant="outline" size="sm">
                                <a href={pdfUrl} target="_blank" rel="noreferrer">
                                    <Download className="size-4 mr-2" /> Download PDF
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {isVoid ? (
                    <Card>
                        <CardContent className="py-8 text-center text-gray-500">
                            This invoice is no longer active.
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Line Items</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-gray-500">
                                        <th className="pb-2">Description</th>
                                        <th className="pb-2 text-right">Qty</th>
                                        <th className="pb-2 text-right">Unit Price</th>
                                        <th className="pb-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoice.lines?.map((line) => (
                                        <tr key={line.id} className="border-b last:border-0">
                                            <td className="py-2">{line.description}</td>
                                            <td className="py-2 text-right">{line.quantity}</td>
                                            <td className="py-2 text-right">{format(line.unit_price)}</td>
                                            <td className="py-2 text-right">
                                                {format(Number(line.quantity) * Number(line.unit_price))}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-4 border-t pt-4 space-y-1 text-sm text-right">
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Subtotal</span>
                                    <span>{formatCurrency(invoice.subtotal, invoice.currency_code)}</span>
                                </div>
                                {Number(invoice.tax_amount) > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Tax</span>
                                        <span>{formatCurrency(invoice.tax_amount, invoice.currency_code)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-semibold text-base border-t pt-2 mt-2">
                                    <span>Total</span>
                                    <span>{formatCurrency(invoice.total, invoice.currency_code)}</span>
                                </div>
                                {Number(invoice.amount_paid) > 0 && (
                                    <div className="flex justify-between text-emerald-600">
                                        <span>Amount Paid</span>
                                        <span>-{formatCurrency(invoice.amount_paid, invoice.currency_code)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-base">
                                    <span>Balance Due</span>
                                    <span>{formatCurrency(invoice.balance_due, invoice.currency_code)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </PortalLayout>
    );
}

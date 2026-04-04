import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useCurrency } from '@/hooks/use-currency';
import PortalLayout from '@/layouts/portal-layout';
import type { Business, Invoice } from '@/types';

type Props = {
    quote: Invoice;
    business: Business;
    alreadyResponded: boolean;
    approveUrl: string;
    rejectUrl: string;
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    sent: 'bg-blue-100 text-blue-700',
    approved: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-rose-100 text-rose-700',
};

export default function QuoteApproval({ quote, business, alreadyResponded, approveUrl, rejectUrl }: Props) {
    const { format } = useCurrency();
    const { flash } = usePage<{ flash: { responded?: string } }>().props;
    const responded = flash?.responded;
    const [action, setAction] = useState<'approve' | 'reject' | null>(null);

    const form = useForm({ comment: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!action) {
            return;
        }
        form.post(action === 'approve' ? approveUrl : rejectUrl);
    }

    if (responded) {
        return (
            <PortalLayout businessName={business.name} logoPath={business.logo_path}>
                <Head title="Quote Response Recorded" />
                <Card className="text-center py-12">
                    <CardContent>
                        {responded === 'approved' ? (
                            <CheckCircle className="size-16 text-emerald-500 mx-auto mb-4" />
                        ) : (
                            <XCircle className="size-16 text-rose-500 mx-auto mb-4" />
                        )}
                        <h2 className="text-2xl font-semibold mb-2">
                            {responded === 'approved' ? 'Quote Approved' : 'Quote Rejected'}
                        </h2>
                        <p className="text-gray-500">Thank you — your response has been recorded.</p>
                    </CardContent>
                </Card>
            </PortalLayout>
        );
    }

    if (alreadyResponded) {
        return (
            <PortalLayout businessName={business.name} logoPath={business.logo_path}>
                <Head title={`Quote ${quote.number}`} />
                <Card>
                    <CardContent className="py-8 text-center">
                        <p className="text-gray-500">This quote has already been responded to.</p>
                        <span className={`mt-3 inline-block px-3 py-1 rounded-full text-sm font-medium ${statusColors[quote.status] ?? 'bg-gray-100 text-gray-600'}`}>
                            {quote.status.replace('_', ' ')}
                        </span>
                    </CardContent>
                </Card>
            </PortalLayout>
        );
    }

    return (
        <PortalLayout businessName={business.name} logoPath={business.logo_path}>
            <Head title={`Quote ${quote.number}`} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">Quote {quote.number}</h1>
                    <p className="text-gray-500 text-sm mt-1">
                        Issued {quote.date}
                        {quote.due_date && ` · Valid until ${quote.due_date}`}
                    </p>
                </div>

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
                                {quote.lines?.map((line) => (
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
                                <span>{format(quote.subtotal)}</span>
                            </div>
                            {Number(quote.tax_amount) > 0 && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Tax</span>
                                    <span>{format(quote.tax_amount)}</span>
                                </div>
                            )}
                            <div className="flex justify-between font-semibold text-base border-t pt-2 mt-2">
                                <span>Total</span>
                                <span>{format(quote.total)}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {quote.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{quote.notes}</p>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Response</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="comment">Comment (optional)</Label>
                                <Textarea
                                    id="comment"
                                    value={form.data.comment}
                                    onChange={(e) => form.setData('comment', e.target.value)}
                                    placeholder="Add any notes or questions..."
                                    className="mt-1"
                                    rows={3}
                                />
                            </div>
                            <div className="flex gap-3">
                                <Button
                                    type="submit"
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white flex-1"
                                    disabled={form.processing}
                                    onClick={() => setAction('approve')}
                                >
                                    <CheckCircle className="size-4 mr-2" /> Approve Quote
                                </Button>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="border-rose-300 text-rose-600 hover:bg-rose-50 flex-1"
                                    disabled={form.processing}
                                    onClick={() => setAction('reject')}
                                >
                                    <XCircle className="size-4 mr-2" /> Reject
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </PortalLayout>
    );
}

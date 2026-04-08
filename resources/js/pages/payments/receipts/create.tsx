import { Head, useForm, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import { CURRENCIES } from '@/lib/currencies';
import { formatCurrency } from '@/lib/utils';
import type { BreadcrumbItem, ContactOption, AccountOption, Invoice } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Receipts', href: '/payments/receipts' },
    { title: 'Receive Payment', href: '/payments/receipts/create' },
];

type Props = {
    customers: ContactOption[];
    bankAccounts: AccountOption[];
    outstandingInvoices: Invoice[];
    preselectedInvoiceId?: number;
};

export default function ReceiptCreate({ customers, bankAccounts, outstandingInvoices }: Props) {
    const { currency: baseCurrency } = useCurrency();
    const { data, setData, post, processing, errors, transform } = useForm({
        contact_id: 'none',
        bank_account_id: 'none',
        date: new Date().toISOString().split('T')[0],
        amount: '',
        currency_code: baseCurrency,
        exchange_rate: '1',
        reference: '',
        description: '',
        allocations: [] as { invoice_id: number; amount: string }[],
    });

    const isForeignCurrency = data.currency_code !== baseCurrency;

    const filteredInvoices = data.contact_id && data.contact_id !== 'none'
        ? outstandingInvoices.filter((inv) => inv.contact_id.toString() === data.contact_id)
        : outstandingInvoices;

    function toggleInvoice(invoiceId: number, balanceDue: number | string) {
        const exists = data.allocations.find((a) => a.invoice_id === invoiceId);

        if (exists) {
            setData('allocations', data.allocations.filter((a) => a.invoice_id !== invoiceId));
        } else {
            const amount = typeof balanceDue === 'string' ? parseFloat(balanceDue) : balanceDue;
            setData('allocations', [...data.allocations, { invoice_id: invoiceId, amount: amount.toFixed(2) }]);
        }
    }

    function updateAllocation(invoiceId: number, amount: string) {
        setData('allocations', data.allocations.map((a) => a.invoice_id === invoiceId ? { ...a, amount } : a));
    }

    const totalAllocated = data.allocations.reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            contact_id: data.contact_id === 'none' ? '' : data.contact_id,
            bank_account_id: data.bank_account_id === 'none' ? '' : data.bank_account_id,
        }));

        post('/payments/receipts');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Receive Payment" />
            <div className="max-w-3xl mx-auto p-4 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>Receive Payment</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="contact_id">Customer *</Label>
                                    <Select value={data.contact_id} onValueChange={(v) => setData('contact_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select customer" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none" disabled>Select customer...</SelectItem>
                                            {customers.map((c) => <SelectItem key={c.id} value={c.id.toString()}>{c.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.contact_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="bank_account_id">Deposit To *</Label>
                                    <Select value={data.bank_account_id} onValueChange={(v) => setData('bank_account_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Bank account" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none" disabled>Select bank account...</SelectItem>
                                            {bankAccounts.map((a) => <SelectItem key={a.id} value={a.id.toString()}>{a.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.bank_account_id} />
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="date">Date *</Label>
                                    <Input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="amount">Amount *</Label>
                                    <Input id="amount" type="number" step="0.01" value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                                    <InputError message={errors.amount} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="reference">Reference</Label>
                                    <Input id="reference" value={data.reference} onChange={(e) => setData('reference', e.target.value)} />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="currency_code">Currency</Label>
                                    <Select value={data.currency_code} onValueChange={(v) => {
                                        setData('currency_code', v);

                                        if (v === baseCurrency) {
 setData('exchange_rate', '1'); 
}
                                    }}>
                                        <SelectTrigger id="currency_code"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {CURRENCIES.map((c) => (
                                                <SelectItem key={c.code} value={c.code}>{c.code} — {c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_code} />
                                </div>
                                {isForeignCurrency && (
                                    <div className="space-y-2">
                                        <Label htmlFor="exchange_rate">Exchange Rate <span className="text-muted-foreground text-xs">(1 {data.currency_code} = ? {baseCurrency})</span></Label>
                                        <Input
                                            id="exchange_rate"
                                            type="number"
                                            step="0.000001"
                                            min="0.000001"
                                            value={data.exchange_rate}
                                            onChange={(e) => setData('exchange_rate', e.target.value)}
                                        />
                                        <InputError message={errors.exchange_rate} />
                                        {data.amount && (
                                            <p className="text-xs text-muted-foreground">
                                                ≈ {formatCurrency(parseFloat(data.amount || '0') * parseFloat(data.exchange_rate || '1'), baseCurrency)} in {baseCurrency}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Allocation */}
                    {filteredInvoices.length > 0 && (
                        <Card>
                            <CardHeader><CardTitle className="text-base">Allocate to Invoices</CardTitle></CardHeader>
                            <CardContent>
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b text-sm text-muted-foreground">
                                            <th className="text-left py-2 w-8"></th>
                                            <th className="text-left py-2">Invoice</th>
                                            <th className="text-right py-2">Balance Due</th>
                                            <th className="text-right py-2 w-36">Allocate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredInvoices.map((inv) => {
                                            const alloc = data.allocations.find((a) => a.invoice_id === inv.id);

                                            return (
                                                <tr key={inv.id} className="border-b last:border-0">
                                                    <td className="py-2">
                                                        <input
                                                            type="checkbox"
                                                            checked={!!alloc}
                                                            onChange={() => toggleInvoice(inv.id, inv.balance_due)}
                                                            className="rounded"
                                                        />
                                                    </td>
                                                    <td className="py-2 font-mono text-sm">{inv.number} <span className="text-muted-foreground ml-1">({inv.date})</span></td>
                                                    <td className="py-2 text-right text-sm">{formatCurrency(inv.balance_due, inv.currency_code)}</td>
                                                    <td className="py-2 text-right">
                                                        {alloc && (
                                                            <Input
                                                                type="number"
                                                                step="0.01"
                                                                className="h-8 text-sm text-right w-full"
                                                                value={alloc.amount}
                                                                onChange={(e) => updateAllocation(inv.id, e.target.value)}
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                                <div className="flex justify-end mt-4 text-sm">
                                    <span className="text-muted-foreground mr-4">Total Allocated:</span>
                                    <span className="font-bold">{formatCurrency(totalAllocated, data.currency_code)}</span>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" asChild><Link href="/payments/receipts">Cancel</Link></Button>
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Record Receipt'}</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}


import { Head, useForm, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import QuoteController from '@/actions/App/Http/Controllers/Sales/QuoteController';
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
import type { BreadcrumbItem, ContactOption, TaxCodeOption, Invoice } from '@/types';

type LineItem = {
    description: string;
    quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_code_id: string;
};

function emptyLine(): LineItem {
    return { description: '', quantity: '1', unit_price: '0', discount_percent: '0', tax_code_id: '' };
}

function calcLineTotal(line: LineItem, taxCodes: TaxCodeOption[]): { subtotal: number; tax: number; total: number } {
    const qty = parseFloat(line.quantity) || 0;
    const price = parseFloat(line.unit_price) || 0;
    const discount = parseFloat(line.discount_percent) || 0;
    const subtotal = qty * price * (1 - discount / 100);
    const taxCode = taxCodes.find((t) => t.id.toString() === line.tax_code_id);
    const tax = taxCode ? subtotal * (taxCode.rate / 100) : 0;

    return { subtotal, tax, total: subtotal + tax };
}

type Props = {
    customers: ContactOption[];
    taxCodes: TaxCodeOption[];
    quote?: Invoice;
};

export default function QuoteForm({ customers, taxCodes, quote }: Props) {
    const { currency: baseCurrency } = useCurrency();
    const isEditing = !!quote;
    const today = new Date().toISOString().split('T')[0];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Quotes', href: QuoteController.index.url() },
        ...(isEditing ? [
            { title: quote!.number, href: QuoteController.show.url(quote!) },
            { title: 'Edit', href: QuoteController.edit.url(quote!) },
        ] : [
            { title: 'New Quote', href: QuoteController.create.url() },
        ]),
    ];

    const { data, setData, post, put, processing, errors, transform } = useForm({
        contact_id: quote?.contact_id?.toString() || '',
        date: quote?.date || today,
        due_date: quote?.due_date || '',
        reference: quote?.reference || '',
        currency_code: quote?.currency_code || baseCurrency,
        exchange_rate: quote?.exchange_rate?.toString() || '1',
        notes: quote?.notes || '',
        terms: quote?.terms || '',
        lines: (quote?.lines || [emptyLine()]).map((l) => ({
            description: l.description || '',
            quantity: l.quantity?.toString() || '1',
            unit_price: l.unit_price?.toString() || '0',
            discount_percent: l.discount_percent?.toString() || '0',
            tax_code_id: l.tax_code_id?.toString() || 'none',
        })),
    });

    const addLine = useCallback(() => {
        setData('lines', [...data.lines, { ...emptyLine(), tax_code_id: 'none' }]);
    }, [data.lines, setData]);

    const removeLine = useCallback((index: number) => {
        if (data.lines.length <= 1) {
return;
}

        setData('lines', data.lines.filter((_, i) => i !== index));
    }, [data.lines, setData]);

    const updateLine = useCallback((index: number, field: keyof LineItem, value: string) => {
        const updated = [...data.lines];
        updated[index] = { ...updated[index], [field]: value };
        setData('lines', updated);
    }, [data.lines, setData]);

    const totals = useMemo(() => {
        let subtotal = 0, tax = 0;
        data.lines.forEach((line) => {
            const calc = calcLineTotal(line, taxCodes);
            subtotal += calc.subtotal;
            tax += calc.tax;
        });

        return { subtotal, tax, total: subtotal + tax };
    }, [data.lines, taxCodes]);

    const isForeignCurrency = data.currency_code !== baseCurrency;

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            lines: data.lines.map(line => ({
                ...line,
                tax_code_id: line.tax_code_id === 'none' ? null : line.tax_code_id
            }))
        }));

        if (isEditing) {
            put(`/sales/quotes/${quote!.id}`);
        } else {
            post('/sales/quotes');
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Edit Quote ${quote!.number}` : 'New Quote'} />
            <div className="max-w-5xl mx-auto p-4 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Header */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{isEditing ? `Edit Quote ${quote!.number}` : 'New Sales Quote'}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="contact_id">Customer *</Label>
                                    <Select value={data.contact_id} onValueChange={(v) => setData('contact_id', v)}>
                                        <SelectTrigger id="contact_id"><SelectValue placeholder="Select customer" /></SelectTrigger>
                                        <SelectContent>
                                            {customers.map((c) => (
                                                <SelectItem key={c.id} value={c.id.toString()}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.contact_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="date">Quote Date *</Label>
                                    <Input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} />
                                    <InputError message={errors.date} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="due_date">Expiry Date</Label>
                                    <Input id="due_date" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                                    <InputError message={errors.due_date} />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="reference">Reference</Label>
                                    <Input id="reference" value={data.reference} onChange={(e) => setData('reference', e.target.value)} placeholder="e.g. Project Phase 1" />
                                    <InputError message={errors.reference} />
                                </div>
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
                            </div>
                            {isForeignCurrency && (
                                <div className="grid grid-cols-2 gap-4">
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
                                        {totals.total > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                ≈ {formatCurrency(totals.total * parseFloat(data.exchange_rate || '1'), baseCurrency)} in {baseCurrency}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Line Items */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Line Items</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addLine}>
                                <Plus className="mr-1 size-4" />
                                Add Line
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[800px]">
                                    <thead>
                                        <tr className="border-b text-sm text-muted-foreground">
                                            <th className="text-left py-2 pr-2">Description</th>
                                            <th className="text-right py-2 pr-2 w-[80px]">Qty</th>
                                            <th className="text-right py-2 pr-2 w-[110px]">Unit Price</th>
                                            <th className="text-right py-2 pr-2 w-[80px]">Disc %</th>
                                            <th className="text-left py-2 pr-2 w-[130px]">Tax</th>
                                            <th className="text-right py-2 pr-2 w-[100px]">Total</th>
                                            <th className="w-[40px]"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.lines.map((line, idx) => {
                                            const calc = calcLineTotal(line, taxCodes);

                                            return (
                                                <tr key={idx} className="border-b last:border-0 align-top">
                                                    <td className="py-2 pr-2">
                                                        <Input
                                                            className="h-9 text-sm"
                                                            placeholder="Description"
                                                            value={line.description}
                                                            onChange={(e) => updateLine(idx, 'description', e.target.value)}
                                                        />
                                                        <InputError message={(errors as any)[`lines.${idx}.description`]} className="mt-1" />
                                                    </td>
                                                    <td className="py-2 pr-2">
                                                        <Input
                                                            className="h-9 text-sm text-right"
                                                            type="number"
                                                            step="0.01"
                                                            value={line.quantity}
                                                            onChange={(e) => updateLine(idx, 'quantity', e.target.value)}
                                                        />
                                                        <InputError message={(errors as any)[`lines.${idx}.quantity`]} className="mt-1" />
                                                    </td>
                                                    <td className="py-2 pr-2">
                                                        <Input
                                                            className="h-9 text-sm text-right"
                                                            type="number"
                                                            step="0.01"
                                                            value={line.unit_price}
                                                            onChange={(e) => updateLine(idx, 'unit_price', e.target.value)}
                                                        />
                                                        <InputError message={(errors as any)[`lines.${idx}.unit_price`]} className="mt-1" />
                                                    </td>
                                                    <td className="py-2 pr-2">
                                                        <Input
                                                            className="h-9 text-sm text-right"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            max="100"
                                                            value={line.discount_percent}
                                                            onChange={(e) => updateLine(idx, 'discount_percent', e.target.value)}
                                                        />
                                                    </td>
                                                    <td className="py-2 pr-2">
                                                        <Select value={line.tax_code_id || 'none'} onValueChange={(v) => updateLine(idx, 'tax_code_id', v)}>
                                                            <SelectTrigger className="h-9 text-xs"><SelectValue placeholder="None" /></SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="none">None</SelectItem>
                                                                {taxCodes.map((t) => (
                                                                    <SelectItem key={t.id} value={t.id.toString()} className="text-xs">
                                                                        {t.name} ({t.rate}%)
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </td>
                                                    <td className="py-2 pr-2 text-right text-sm font-medium pt-4">
                                                        {formatCurrency(calc.total, data.currency_code)}
                                                    </td>
                                                    <td className="py-2 pt-3">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-muted-foreground hover:text-red-600"
                                                            onClick={() => removeLine(idx)}
                                                            disabled={data.lines.length <= 1}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {/* Totals */}
                            <div className="flex justify-end mt-6">
                                <div className="w-64 space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Subtotal</span>
                                        <span className="font-medium">{formatCurrency(totals.subtotal, data.currency_code)}</span>
                                    </div>
                                    {totals.tax > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span className="text-muted-foreground">Tax</span>
                                            <span className="font-medium">{formatCurrency(totals.tax, data.currency_code)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between text-lg font-bold border-t pt-2">
                                        <span>Total</span>
                                        <span>{formatCurrency(totals.total, data.currency_code)}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notes & Terms */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notes</Label>
                                    <textarea
                                        id="notes"
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        placeholder="Internal notes or description"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="terms">Terms & Conditions</Label>
                                    <textarea
                                        id="terms"
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        value={data.terms}
                                        onChange={(e) => setData('terms', e.target.value)}
                                        placeholder="Quote validity, delivery, etc."
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" asChild>
                            <Link href="/sales/quotes">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : isEditing ? 'Update Quote' : 'Create Quote'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

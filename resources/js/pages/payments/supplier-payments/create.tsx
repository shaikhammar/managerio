import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ContactOption, AccountOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Supplier Payments', href: '/payments/supplier-payments' },
    { title: 'Make Payment', href: '/payments/supplier-payments/create' },
];

type Props = {
    suppliers: ContactOption[];
    bankAccounts: AccountOption[];
};

export default function SupplierPaymentCreate({ suppliers, bankAccounts }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        contact_id: 'none',
        bank_account_id: 'none',
        date: new Date().toISOString().split('T')[0],
        amount: '',
        reference: '',
        description: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            contact_id: data.contact_id === 'none' ? '' : data.contact_id,
            bank_account_id: data.bank_account_id === 'none' ? '' : data.bank_account_id,
        }));

        post('/payments/supplier-payments');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Make Supplier Payment" />
            <div className="max-w-2xl mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>Make Supplier Payment</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="contact_id">Supplier *</Label>
                                    <Select value={data.contact_id} onValueChange={(v) => setData('contact_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select supplier" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none" disabled>Select supplier...</SelectItem>
                                            {suppliers.map((s) => <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.contact_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="bank_account_id">Pay From *</Label>
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
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                            </div>
                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild><Link href="/payments/supplier-payments">Cancel</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Record Payment'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

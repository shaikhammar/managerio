import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { Account, BreadcrumbItem } from '@/types';

type Props = {
    bankAccounts: Pick<Account, 'id' | 'name' | 'code'>[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Transactions', href: '/banking/transactions' },
    { title: 'New Transaction', href: '/banking/transactions/create' },
];

export default function BankTransactionCreate({ bankAccounts }: Props) {
    const today = new Date().toISOString().split('T')[0];

    const { data, setData, post, processing, errors } = useForm({
        bank_account_id: '',
        date: today,
        description: '',
        amount: '',
        reference: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/banking/transactions');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Bank Transaction" />
            <div className="max-w-lg mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>New Manual Bank Transaction</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="bank_account_id">Bank Account *</Label>
                                <Select value={data.bank_account_id} onValueChange={(v) => setData('bank_account_id', v)}>
                                    <SelectTrigger id="bank_account_id">
                                        <SelectValue placeholder="Select account" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankAccounts.map((a) => (
                                            <SelectItem key={a.id} value={a.id.toString()}>
                                                {a.name} ({a.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.bank_account_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="date">Date *</Label>
                                <Input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required />
                                <InputError message={errors.date} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description *</Label>
                                <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} required />
                                <InputError message={errors.description} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="amount">
                                    Amount * <span className="text-xs text-muted-foreground">(negative for payments out)</span>
                                </Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)}
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reference">Reference</Label>
                                <Input id="reference" value={data.reference} onChange={(e) => setData('reference', e.target.value)} />
                                <InputError message={errors.reference} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/banking/transactions">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Transaction'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

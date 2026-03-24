import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { Account, BankTransaction, BreadcrumbItem } from '@/types';

type Props = {
    transaction: BankTransaction & { bank_account?: Account };
};

export default function BankTransactionEdit({ transaction }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Bank Transactions', href: '/banking/transactions' },
        { title: transaction.description, href: `/banking/transactions/${transaction.id}` },
        { title: 'Edit', href: `/banking/transactions/${transaction.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        date: transaction.date,
        description: transaction.description,
        amount: transaction.amount.toString(),
        reference: transaction.reference || '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/banking/transactions/${transaction.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Transaction`} />
            <div className="max-w-lg mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>Edit Transaction</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label>Bank Account</Label>
                                <p className="text-sm text-muted-foreground py-2">
                                    {transaction.bank_account?.name} ({transaction.bank_account?.code})
                                </p>
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
                                    <Link href={`/banking/transactions/${transaction.id}`}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

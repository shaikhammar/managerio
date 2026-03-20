import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { Account, BreadcrumbItem } from '@/types';

type Props = {
    bankAccounts: Pick<Account, 'id' | 'name' | 'code'>[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Reconciliations', href: '/banking/reconciliations' },
    { title: 'New Reconciliation', href: '/banking/reconciliations/create' },
];

export default function ReconciliationCreate({ bankAccounts }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        bank_account_id: 'none',
        statement_date: new Date().toISOString().split('T')[0],
        statement_balance: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            bank_account_id: data.bank_account_id === 'none' ? '' : data.bank_account_id,
        }));

        post('/banking/reconciliations');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Bank Reconciliation" />
            <div className="max-w-xl mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>New Bank Reconciliation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="bank_account_id">Bank Account *</Label>
                                <Select value={data.bank_account_id} onValueChange={(v) => setData('bank_account_id', v)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select bank account" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none" disabled>Select account...</SelectItem>
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
                                <Label htmlFor="statement_date">Statement Date *</Label>
                                <Input
                                    id="statement_date"
                                    type="date"
                                    value={data.statement_date}
                                    onChange={(e) => setData('statement_date', e.target.value)}
                                    required
                                />
                                <InputError message={errors.statement_date} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="statement_balance">Statement Balance *</Label>
                                <Input
                                    id="statement_balance"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    value={data.statement_balance}
                                    onChange={(e) => setData('statement_balance', e.target.value)}
                                    required
                                />
                                <InputError message={errors.statement_balance} />
                                <p className="text-[10px] text-muted-foreground">
                                    The balance shown on your bank statement as of the date selected.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/banking/reconciliations">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Start Reconciliation
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

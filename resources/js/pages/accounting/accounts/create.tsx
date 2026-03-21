import { Head, useForm, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Account } from '@/types';

type Props = {
    account?: Account;
    accountTypes: { value: string; label: string }[];
};

const subTypes: Record<string, { value: string; label: string }[]> = {
    asset: [
        { value: 'cash', label: 'Cash' }, { value: 'bank', label: 'Bank' },
        { value: 'accounts_receivable', label: 'Accounts Receivable' }, { value: 'prepaid_expense', label: 'Prepaid Expense' },
        { value: 'other_current_asset', label: 'Other Current Asset' }, { value: 'fixed_asset', label: 'Fixed Asset' },
    ],
    liability: [
        { value: 'accounts_payable', label: 'Accounts Payable' }, { value: 'tax_payable', label: 'Tax Payable' },
        { value: 'credit_card', label: 'Credit Card' }, { value: 'other_current_liability', label: 'Other Current Liability' },
        { value: 'long_term_liability', label: 'Long Term Liability' },
    ],
    equity: [
        { value: 'owner_equity', label: 'Owner Equity' }, { value: 'retained_earnings', label: 'Retained Earnings' },
    ],
    revenue: [
        { value: 'sales_revenue', label: 'Sales Revenue' }, { value: 'service_revenue', label: 'Service Revenue' },
        { value: 'other_revenue', label: 'Other Revenue' },
    ],
    expense: [
        { value: 'cost_of_services', label: 'Cost of Services' }, { value: 'operating_expense', label: 'Operating Expense' },
        { value: 'payroll_expense', label: 'Payroll Expense' }, { value: 'depreciation', label: 'Depreciation' },
        { value: 'other_expense', label: 'Other Expense' },
    ],
};

export default function AccountForm({ account, accountTypes }: Props) {
    const isEditing = !!account;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Chart of Accounts', href: '/accounting/accounts' },
        { title: isEditing ? `Edit ${account.name}` : 'New Account', href: '#' },
    ];

    const { data, setData, post, put, processing, errors, transform } = useForm({
        code: account?.code || '',
        name: account?.name || '',
        type: account?.type || 'none',
        sub_type: account?.sub_type || 'none',
        description: account?.description || '',
        is_active: account?.is_active ?? true,
    });

    const availableSubTypes = data.type && data.type !== 'none' ? (subTypes[data.type] || []) : [];

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        transform((data) => ({
            ...data,
            type: data.type === 'none' ? '' : data.type,
            sub_type: data.sub_type === 'none' ? '' : data.sub_type,
        }));

        if (isEditing) {
            put(`/accounting/accounts/${account!.id}`);
        } else {
            post('/accounting/accounts');
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Edit ${account!.name}` : 'New Account'} />
            <div className="max-w-xl mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>{isEditing ? 'Edit Account' : 'New Account'}</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code *</Label>
                                    <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="1000" required />
                                    <InputError message={errors.code} />
                                </div>
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="name">Name *</Label>
                                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                    <InputError message={errors.name} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="type">Type *</Label>
                                    <Select value={data.type} onValueChange={(v) => {
 setData('type', v); setData('sub_type', 'none'); 
}}>
                                        <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none" disabled>Select type...</SelectItem>
                                            {accountTypes.map((t) => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.type} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="sub_type">Sub Type</Label>
                                    <Select value={data.sub_type} onValueChange={(v) => setData('sub_type', v)} disabled={data.type === 'none'}>
                                        <SelectTrigger><SelectValue placeholder="Select sub type" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">None</SelectItem>
                                            {availableSubTypes.map((s) => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                            </div>

                            {isEditing && (
                                <div className="flex items-center gap-2">
                                    <Checkbox id="is_active" checked={data.is_active} onCheckedChange={(c) => setData('is_active', c as boolean)} />
                                    <Label htmlFor="is_active">Active</Label>
                                </div>
                            )}

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild><Link href="/accounting/accounts">Cancel</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Saving...' : isEditing ? 'Save' : 'Create Account'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

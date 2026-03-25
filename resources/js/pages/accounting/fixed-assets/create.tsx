import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { Account, BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fixed Assets', href: '/accounting/fixed-assets' },
    { title: 'New Asset', href: '/accounting/fixed-assets/create' },
];

type MethodOption = { value: string; label: string };

type Props = {
    accounts: Account[];
    methods: MethodOption[];
};

export default function FixedAssetCreate({ accounts, methods }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        asset_tag: '',
        asset_account_id: '',
        accumulated_depreciation_account_id: '',
        depreciation_expense_account_id: '',
        purchase_date: new Date().toISOString().split('T')[0],
        purchase_cost: '',
        salvage_value: '0',
        useful_life_months: '60',
        depreciation_method: 'straight_line',
        notes: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/accounting/fixed-assets');
    }

    const assetAccounts = accounts.filter((a) => a.sub_type === 'fixed_asset');
    const accumDepAccounts = accounts.filter((a) => a.type === 'asset');
    const expenseAccounts = accounts.filter((a) => a.sub_type === 'depreciation' || a.type === 'expense');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Fixed Asset" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-3xl">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Register Fixed Asset</h1>
                    <p className="text-muted-foreground text-sm">Add a new asset to the fixed asset register</p>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Asset Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2 flex flex-col gap-1.5">
                                <Label htmlFor="name">Asset Name <span className="text-destructive">*</span></Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Office Laptop Dell XPS 15"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="asset_tag">Asset Tag</Label>
                                <Input
                                    id="asset_tag"
                                    value={data.asset_tag}
                                    onChange={(e) => setData('asset_tag', e.target.value)}
                                    placeholder="e.g. FA-0001"
                                />
                                <InputError message={errors.asset_tag} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="purchase_date">Purchase Date <span className="text-destructive">*</span></Label>
                                <Input
                                    id="purchase_date"
                                    type="date"
                                    value={data.purchase_date}
                                    onChange={(e) => setData('purchase_date', e.target.value)}
                                />
                                <InputError message={errors.purchase_date} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="purchase_cost">Purchase Cost <span className="text-destructive">*</span></Label>
                                <Input
                                    id="purchase_cost"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    value={data.purchase_cost}
                                    onChange={(e) => setData('purchase_cost', e.target.value)}
                                    placeholder="0.00"
                                />
                                <InputError message={errors.purchase_cost} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="salvage_value">Salvage Value</Label>
                                <Input
                                    id="salvage_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.salvage_value}
                                    onChange={(e) => setData('salvage_value', e.target.value)}
                                    placeholder="0.00"
                                />
                                <InputError message={errors.salvage_value} />
                            </div>

                            <div className="sm:col-span-2 flex flex-col gap-1.5">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Optional description"
                                    rows={2}
                                />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Depreciation</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="depreciation_method">Method <span className="text-destructive">*</span></Label>
                                <Select value={data.depreciation_method} onValueChange={(v) => setData('depreciation_method', v)}>
                                    <SelectTrigger id="depreciation_method">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {methods.map((m) => (
                                            <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.depreciation_method} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="useful_life_months">Useful Life (months) <span className="text-destructive">*</span></Label>
                                <Input
                                    id="useful_life_months"
                                    type="number"
                                    min="1"
                                    max="600"
                                    value={data.useful_life_months}
                                    onChange={(e) => setData('useful_life_months', e.target.value)}
                                    placeholder="60"
                                />
                                <InputError message={errors.useful_life_months} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Accounts</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="asset_account_id">Asset Account <span className="text-destructive">*</span></Label>
                                <Select value={data.asset_account_id} onValueChange={(v) => setData('asset_account_id', v)}>
                                    <SelectTrigger id="asset_account_id">
                                        <SelectValue placeholder="Select fixed asset account..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(assetAccounts.length ? assetAccounts : accounts).map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>
                                                {a.code} — {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.asset_account_id} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="accumulated_depreciation_account_id">Accumulated Depreciation Account <span className="text-destructive">*</span></Label>
                                <Select value={data.accumulated_depreciation_account_id} onValueChange={(v) => setData('accumulated_depreciation_account_id', v)}>
                                    <SelectTrigger id="accumulated_depreciation_account_id">
                                        <SelectValue placeholder="Select accumulated depreciation account..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(accumDepAccounts.length ? accumDepAccounts : accounts).map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>
                                                {a.code} — {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.accumulated_depreciation_account_id} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="depreciation_expense_account_id">Depreciation Expense Account <span className="text-destructive">*</span></Label>
                                <Select value={data.depreciation_expense_account_id} onValueChange={(v) => setData('depreciation_expense_account_id', v)}>
                                    <SelectTrigger id="depreciation_expense_account_id">
                                        <SelectValue placeholder="Select depreciation expense account..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(expenseAccounts.length ? expenseAccounts : accounts).map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>
                                                {a.code} — {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.depreciation_expense_account_id} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" asChild>
                            <Link href="/accounting/fixed-assets">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Register Asset'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

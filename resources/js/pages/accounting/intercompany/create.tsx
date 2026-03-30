import { Head, useForm } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { AccountOption, BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Intercompany Transactions', href: '/accounting/intercompany' },
    { title: 'New Transfer', href: '/accounting/intercompany/create' },
];

type OtherBusiness = { id: number; name: string };

type Props = {
    sourceAccounts: AccountOption[];
    otherBusinesses: OtherBusiness[];
};

export default function IntercompanyCreate({ sourceAccounts, otherBusinesses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        source_account_id: '',
        target_business_id: '',
        target_account_id: '',
        amount: '',
        date: new Date().toISOString().slice(0, 10),
        description: '',
        reference: '',
    });

    const [targetAccounts, setTargetAccounts] = useState<AccountOption[]>([]);
    const [loadingTargetAccounts, setLoadingTargetAccounts] = useState(false);

    function handleTargetBusinessChange(businessId: string) {
        setData('target_business_id', businessId);
        setData('target_account_id', '');

        if (!businessId) {
            setTargetAccounts([]);

            return;
        }

        setLoadingTargetAccounts(true);
        fetch(`/accounting/intercompany/target-accounts?business_id=${businessId}`)
            .then((r) => r.json())
            .then((accounts) => setTargetAccounts(accounts))
            .finally(() => setLoadingTargetAccounts(false));
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/accounting/intercompany');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Intercompany Transfer" />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-2xl">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">New Intercompany Transfer</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Transfer funds or record a charge between two businesses you manage.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-5">
                    {/* Date & Amount */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="date">Date</Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) => setData('date', e.target.value)}
                            />
                            {errors.date && <p className="text-destructive text-xs">{errors.date}</p>}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="amount">Amount</Label>
                            <Input
                                id="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                placeholder="0.00"
                                value={data.amount}
                                onChange={(e) => setData('amount', e.target.value)}
                            />
                            {errors.amount && <p className="text-destructive text-xs">{errors.amount}</p>}
                        </div>
                    </div>

                    {/* Description */}
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="description">Description</Label>
                        <Input
                            id="description"
                            placeholder="e.g. Management fee Q1 2026"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        {errors.description && <p className="text-destructive text-xs">{errors.description}</p>}
                    </div>

                    {/* Source Account */}
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="source_account_id">Source Account (this business)</Label>
                        <select
                            id="source_account_id"
                            className="border rounded-md px-3 py-2 text-sm bg-background"
                            value={data.source_account_id}
                            onChange={(e) => setData('source_account_id', e.target.value)}
                        >
                            <option value="">Select account…</option>
                            {sourceAccounts.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.code} · {a.name}
                                </option>
                            ))}
                        </select>
                        {errors.source_account_id && (
                            <p className="text-destructive text-xs">{errors.source_account_id}</p>
                        )}
                    </div>

                    {/* Target Business */}
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="target_business_id">Target Business</Label>
                        {otherBusinesses.length === 0 ? (
                            <p className="text-sm text-muted-foreground italic">
                                You have no other businesses to transfer to.
                            </p>
                        ) : (
                            <select
                                id="target_business_id"
                                className="border rounded-md px-3 py-2 text-sm bg-background"
                                value={data.target_business_id}
                                onChange={(e) => handleTargetBusinessChange(e.target.value)}
                            >
                                <option value="">Select business…</option>
                                {otherBusinesses.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name}
                                    </option>
                                ))}
                            </select>
                        )}
                        {errors.target_business_id && (
                            <p className="text-destructive text-xs">{errors.target_business_id}</p>
                        )}
                    </div>

                    {/* Target Account */}
                    {data.target_business_id && (
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="target_account_id">Target Account</Label>
                            <select
                                id="target_account_id"
                                className="border rounded-md px-3 py-2 text-sm bg-background"
                                value={data.target_account_id}
                                onChange={(e) => setData('target_account_id', e.target.value)}
                                disabled={loadingTargetAccounts}
                            >
                                <option value="">
                                    {loadingTargetAccounts ? 'Loading…' : 'Select account…'}
                                </option>
                                {targetAccounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.code} · {a.name}
                                    </option>
                                ))}
                            </select>
                            {errors.target_account_id && (
                                <p className="text-destructive text-xs">{errors.target_account_id}</p>
                            )}
                        </div>
                    )}

                    {/* Reference */}
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="reference">Reference <span className="text-muted-foreground font-normal">(optional)</span></Label>
                        <Input
                            id="reference"
                            placeholder="e.g. INV-001"
                            value={data.reference}
                            onChange={(e) => setData('reference', e.target.value)}
                        />
                        {errors.reference && <p className="text-destructive text-xs">{errors.reference}</p>}
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : 'Record Transfer'}
                        </Button>
                        <Button variant="outline" type="button" asChild>
                            <a href="/accounting/intercompany">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

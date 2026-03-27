import { Head, Link, router, useForm } from '@inertiajs/react';
import { Edit, Trash2, TrendingDown, Ban, DollarSign } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { Account, BreadcrumbItem, DepreciationEntry, FixedAsset, JournalEntry } from '@/types';

type ScheduleRow = {
    period: string;
    depreciation: string;
    accumulated: string;
    book_value: string;
};

type AssetWithComputed = FixedAsset & {
    accumulated_depreciation: string;
    book_value: string;
    is_fully_depreciated: boolean;
    asset_account?: Account;
    accumulated_depreciation_account?: Account;
    depreciation_expense_account?: Account;
    depreciation_entries?: (DepreciationEntry & { journal_entry?: JournalEntry })[];
};

type Props = {
    asset: AssetWithComputed;
    schedule: ScheduleRow[];
};

const statusColors: Record<string, string> = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    retired: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    disposed: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
};

export default function FixedAssetShow({ asset, schedule }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Fixed Assets', href: '/accounting/fixed-assets' },
        { title: asset.name, href: `/accounting/fixed-assets/${asset.id}` },
    ];

    const { format } = useCurrency();
    const [showDisposeForm, setShowDisposeForm] = useState(false);

    const disposeForm = useForm({
        disposal_date: new Date().toISOString().split('T')[0],
        disposal_proceeds: '0',
        bank_account_id: '',
        gain_loss_account_id: '',
    });

    function handleRetire() {
        if (confirm('Mark this asset as retired? This will change its status but not post any journal entries.')) {
            router.post(`/accounting/fixed-assets/${asset.id}/retire`);
        }
    }

    function handleDelete() {
        if (confirm('Delete this asset? This cannot be undone.')) {
            router.delete(`/accounting/fixed-assets/${asset.id}`);
        }
    }

    function handleDispose(e: React.FormEvent) {
        e.preventDefault();
        disposeForm.post(`/accounting/fixed-assets/${asset.id}/dispose`, {
            onSuccess: () => setShowDisposeForm(false),
        });
    }

    const isActive = asset.status === 'active';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={asset.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3 mb-1">
                            <h1 className="text-2xl font-bold tracking-tight">{asset.name}</h1>
                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColors[asset.status] ?? ''}`}>
                                {asset.status}
                            </span>
                        </div>
                        {asset.asset_tag && <p className="text-muted-foreground text-sm">{asset.asset_tag}</p>}
                    </div>
                    <div className="flex items-center gap-2 flex-wrap justify-end">
                        {isActive && (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/accounting/fixed-assets/${asset.id}/edit`}>
                                        <Edit className="mr-1.5 size-3.5" /> Edit
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleRetire}>
                                    <Ban className="mr-1.5 size-3.5" /> Retire
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setShowDisposeForm(!showDisposeForm)}>
                                    <DollarSign className="mr-1.5 size-3.5" /> Dispose
                                </Button>
                            </>
                        )}
                        {!asset.depreciation_entries?.length && (
                            <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={handleDelete}>
                                <Trash2 className="mr-1.5 size-3.5" /> Delete
                            </Button>
                        )}
                    </div>
                </div>

                {/* Disposal form */}
                {showDisposeForm && (
                    <Card className="border-amber-200 dark:border-amber-800">
                        <CardHeader>
                            <CardTitle className="text-base">Dispose Asset</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleDispose} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="disposal_date">Disposal Date <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="disposal_date"
                                        type="date"
                                        value={disposeForm.data.disposal_date}
                                        onChange={(e) => disposeForm.setData('disposal_date', e.target.value)}
                                    />
                                    <InputError message={disposeForm.errors.disposal_date} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="disposal_proceeds">Proceeds</Label>
                                    <Input
                                        id="disposal_proceeds"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={disposeForm.data.disposal_proceeds}
                                        onChange={(e) => disposeForm.setData('disposal_proceeds', e.target.value)}
                                    />
                                    <InputError message={disposeForm.errors.disposal_proceeds} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="gain_loss_account_id">Gain/Loss Account <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="gain_loss_account_id"
                                        type="number"
                                        placeholder="Account ID"
                                        value={disposeForm.data.gain_loss_account_id}
                                        onChange={(e) => disposeForm.setData('gain_loss_account_id', e.target.value)}
                                    />
                                    <InputError message={disposeForm.errors.gain_loss_account_id} />
                                </div>
                                <div className="sm:col-span-2 flex gap-3 justify-end">
                                    <Button type="button" variant="outline" onClick={() => setShowDisposeForm(false)}>Cancel</Button>
                                    <Button type="submit" disabled={disposeForm.processing}>
                                        {disposeForm.processing ? 'Processing...' : 'Post Disposal Entry'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* Key metrics */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    {[
                        { label: 'Purchase Cost', value: format(Number(asset.purchase_cost)) },
                        { label: 'Accumulated Dep.', value: format(Number(asset.accumulated_depreciation)) },
                        { label: 'Book Value', value: format(Number(asset.book_value)) },
                        { label: 'Salvage Value', value: format(Number(asset.salvage_value)) },
                    ].map(({ label, value }) => (
                        <div key={label} className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground mb-1">{label}</p>
                            <p className="text-lg font-semibold">{value}</p>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Asset info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {[
                                { label: 'Purchase Date', value: asset.purchase_date },
                                { label: 'Useful Life', value: `${asset.useful_life_months} months` },
                                { label: 'Depreciation Method', value: asset.depreciation_method === 'straight_line' ? 'Straight Line' : 'Declining Balance' },
                                { label: 'Asset Account', value: asset.asset_account ? `${asset.asset_account.code} — ${asset.asset_account.name}` : '—' },
                                ...(asset.disposal_date ? [
                                    { label: 'Disposal Date', value: asset.disposal_date },
                                    { label: 'Disposal Proceeds', value: format(Number(asset.disposal_proceeds ?? 0)) },
                                ] : []),
                            ].map(({ label, value }) => (
                                <div key={label} className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">{label}</span>
                                    <span className="font-medium">{value}</span>
                                </div>
                            ))}
                            {asset.description && (
                                <p className="text-sm text-muted-foreground pt-2 border-t">{asset.description}</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Depreciation history */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Depreciation History</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {!asset.depreciation_entries?.length ? (
                                <div className="py-6 text-center">
                                    <TrendingDown className="size-8 mx-auto mb-2 text-muted-foreground/30" />
                                    <p className="text-sm text-muted-foreground">No depreciation posted yet</p>
                                </div>
                            ) : (
                                <div className="rounded border overflow-hidden">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="text-left py-2 px-3 font-medium text-muted-foreground">Period</th>
                                                <th className="text-right py-2 px-3 font-medium text-muted-foreground">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {asset.depreciation_entries.map((entry) => (
                                                <tr key={entry.id} className="border-b last:border-0">
                                                    <td className="py-2 px-3 text-muted-foreground">{entry.period_start.slice(0, 7)}</td>
                                                    <td className="py-2 px-3 text-right">{format(Number(entry.depreciation_amount))}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Depreciation schedule */}
                {schedule.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Depreciation Schedule</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded border overflow-hidden overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="text-left py-2 px-4 font-medium text-muted-foreground">Period</th>
                                            <th className="text-right py-2 px-4 font-medium text-muted-foreground">Depreciation</th>
                                            <th className="text-right py-2 px-4 font-medium text-muted-foreground">Accumulated</th>
                                            <th className="text-right py-2 px-4 font-medium text-muted-foreground">Book Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schedule.map((row) => (
                                            <tr key={row.period} className="border-b last:border-0 hover:bg-muted/30">
                                                <td className="py-2 px-4 text-muted-foreground">{row.period}</td>
                                                <td className="py-2 px-4 text-right">{format(Number(row.depreciation))}</td>
                                                <td className="py-2 px-4 text-right">{format(Number(row.accumulated))}</td>
                                                <td className="py-2 px-4 text-right font-medium">{format(Number(row.book_value))}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

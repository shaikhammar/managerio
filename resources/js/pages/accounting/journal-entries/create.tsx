import { Head, useForm, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, AccountOption } from '@/types';
import { useCallback, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal Entries', href: '/accounting/journal-entries' },
    { title: 'New Entry', href: '/accounting/journal-entries/create' },
];

type JournalLine = {
    account_id: string;
    description: string;
    debit: string;
    credit: string;
};

function emptyLine(): JournalLine {
    return { account_id: '', description: '', debit: '', credit: '' };
}

type Props = { accounts: AccountOption[] };

export default function JournalEntryCreate({ accounts }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        date: new Date().toISOString().split('T')[0],
        description: '',
        reference: '',
        lines: [emptyLine(), emptyLine()],
    });

    const addLine = useCallback(() => {
        setData('lines', [...data.lines, emptyLine()]);
    }, [data.lines, setData]);

    const removeLine = useCallback((index: number) => {
        if (data.lines.length <= 2) return;
        setData('lines', data.lines.filter((_, i) => i !== index));
    }, [data.lines, setData]);

    const updateLine = useCallback((index: number, field: keyof JournalLine, value: string) => {
        const updated = [...data.lines];
        updated[index] = { ...updated[index], [field]: value };
        // Clear opposite side when value entered
        if (field === 'debit' && value) updated[index].credit = '';
        if (field === 'credit' && value) updated[index].debit = '';
        setData('lines', updated);
    }, [data.lines, setData]);

    const totals = useMemo(() => {
        const debit = data.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
        const credit = data.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
        return { debit, credit, balanced: Math.abs(debit - credit) < 0.01 };
    }, [data.lines]);

    function fmt(n: number) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(n);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/accounting/journal-entries');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Journal Entry" />
            <div className="max-w-5xl mx-auto p-4 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>New Journal Entry</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="date">Date *</Label>
                                    <Input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} />
                                    <InputError message={errors.date} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} placeholder="Journal entry description" />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="reference">Reference</Label>
                                    <Input id="reference" value={data.reference} onChange={(e) => setData('reference', e.target.value)} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Lines</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addLine}>
                                <Plus className="mr-1 size-4" /> Add Line
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b text-sm text-muted-foreground">
                                        <th className="text-left py-2 pr-2 w-[220px]">Account *</th>
                                        <th className="text-left py-2 pr-2">Description</th>
                                        <th className="text-right py-2 pr-2 w-[130px]">Debit</th>
                                        <th className="text-right py-2 pr-2 w-[130px]">Credit</th>
                                        <th className="w-[40px]"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.lines.map((line, idx) => (
                                        <tr key={idx} className="border-b last:border-0 align-top">
                                            <td className="py-2 pr-2">
                                                <Select value={line.account_id} onValueChange={(v) => updateLine(idx, 'account_id', v)}>
                                                    <SelectTrigger className="h-9 text-xs"><SelectValue placeholder="Account" /></SelectTrigger>
                                                    <SelectContent>
                                                        {accounts.map((a) => (
                                                            <SelectItem key={a.id} value={a.id.toString()} className="text-xs">{a.code} · {a.name}</SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input className="h-9 text-sm" value={line.description} onChange={(e) => updateLine(idx, 'description', e.target.value)} placeholder="Line description" />
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input className="h-9 text-sm text-right" type="number" step="0.01" value={line.debit} onChange={(e) => updateLine(idx, 'debit', e.target.value)} placeholder="0.00" />
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input className="h-9 text-sm text-right" type="number" step="0.01" value={line.credit} onChange={(e) => updateLine(idx, 'credit', e.target.value)} placeholder="0.00" />
                                            </td>
                                            <td className="py-2 pt-3">
                                                <Button type="button" variant="ghost" size="icon" className="size-8 text-muted-foreground hover:text-red-600" onClick={() => removeLine(idx)} disabled={data.lines.length <= 2}>
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t-2 font-bold">
                                        <td colSpan={2} className="py-2">Totals</td>
                                        <td className="py-2 text-right">{fmt(totals.debit)}</td>
                                        <td className="py-2 text-right">{fmt(totals.credit)}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>

                            {!totals.balanced && totals.debit + totals.credit > 0 && (
                                <div className="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-400">
                                    ⚠️ Entry is unbalanced. Difference: {fmt(Math.abs(totals.debit - totals.credit))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" asChild><Link href="/accounting/journal-entries">Cancel</Link></Button>
                        <Button type="submit" disabled={processing || !totals.balanced}>
                            {processing ? 'Saving...' : 'Create Entry'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

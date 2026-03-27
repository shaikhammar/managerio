import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { AccountOption, BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recurring Journal Entries', href: '/accounting/recurring-journal-entries' },
    { title: 'New', href: '/accounting/recurring-journal-entries/create' },
];

type FrequencyOption = { value: string; label: string };

type TemplateLine = {
    account_id: string;
    description: string;
    debit: string;
    credit: string;
};

function emptyLine(): TemplateLine {
    return { account_id: 'none', description: '', debit: '', credit: '' };
}

type Props = {
    accounts: AccountOption[];
    frequencies: FrequencyOption[];
};

export default function RecurringJournalEntryCreate({ accounts, frequencies }: Props) {
    const { format } = useCurrency();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        frequency: 'monthly',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        day_of_month: '1',
        template_lines: [emptyLine(), emptyLine()],
    });

    const addLine = useCallback(() => {
        setData('template_lines', [...data.template_lines, emptyLine()]);
    }, [data.template_lines, setData]);

    const removeLine = useCallback((index: number) => {
        if (data.template_lines.length <= 2) {
            return;
        }

        setData('template_lines', data.template_lines.filter((_, i) => i !== index));
    }, [data.template_lines, setData]);

    const updateLine = useCallback((index: number, field: keyof TemplateLine, value: string) => {
        const updated = [...data.template_lines];
        updated[index] = { ...updated[index], [field]: value };

        if (field === 'debit' && value) {
            updated[index].credit = '';
        }

        if (field === 'credit' && value) {
            updated[index].debit = '';
        }

        setData('template_lines', updated);
    }, [data.template_lines, setData]);

    const totals = useMemo(() => {
        const debit = data.template_lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
        const credit = data.template_lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);

        return { debit, credit, balanced: Math.abs(debit - credit) < 0.01 };
    }, [data.template_lines]);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/accounting/recurring-journal-entries');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Recurring Journal Entry" />
            <div className="max-w-5xl mx-auto p-4 md:p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader><CardTitle>New Recurring Journal Entry</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2 col-span-2">
                                    <Label htmlFor="name">Name *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="e.g. Monthly rent, Depreciation"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2 col-span-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Input
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </div>

                            <div className="grid grid-cols-4 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="frequency">Frequency *</Label>
                                    <Select value={data.frequency} onValueChange={(v) => setData('frequency', v)}>
                                        <SelectTrigger id="frequency"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {frequencies.map((f) => (
                                                <SelectItem key={f.value} value={f.value}>{f.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.frequency} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="day_of_month">Day of Month *</Label>
                                    <Input
                                        id="day_of_month"
                                        type="number"
                                        min="1"
                                        max="28"
                                        value={data.day_of_month}
                                        onChange={(e) => setData('day_of_month', e.target.value)}
                                    />
                                    <InputError message={errors.day_of_month} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="start_date">Start Date *</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="end_date">End Date</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Template Lines</CardTitle>
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
                                    {data.template_lines.map((line, idx) => (
                                        <tr key={idx} className="border-b last:border-0 align-top">
                                            <td className="py-2 pr-2">
                                                <Select value={line.account_id} onValueChange={(v) => updateLine(idx, 'account_id', v)}>
                                                    <SelectTrigger className="h-9 text-xs"><SelectValue placeholder="Account" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="none" disabled>Select account...</SelectItem>
                                                        {accounts.map((a) => (
                                                            <SelectItem key={a.id} value={a.id.toString()} className="text-xs">
                                                                {a.code} · {a.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={(errors as any)[`template_lines.${idx}.account_id`]} className="mt-1" />
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input
                                                    className="h-9 text-sm"
                                                    value={line.description}
                                                    onChange={(e) => updateLine(idx, 'description', e.target.value)}
                                                    placeholder="Line description"
                                                />
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input
                                                    className="h-9 text-sm text-right"
                                                    type="number"
                                                    step="0.01"
                                                    value={line.debit}
                                                    onChange={(e) => updateLine(idx, 'debit', e.target.value)}
                                                    placeholder="0.00"
                                                />
                                            </td>
                                            <td className="py-2 pr-2">
                                                <Input
                                                    className="h-9 text-sm text-right"
                                                    type="number"
                                                    step="0.01"
                                                    value={line.credit}
                                                    onChange={(e) => updateLine(idx, 'credit', e.target.value)}
                                                    placeholder="0.00"
                                                />
                                            </td>
                                            <td className="py-2 pt-3">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-muted-foreground hover:text-red-600"
                                                    onClick={() => removeLine(idx)}
                                                    disabled={data.template_lines.length <= 2}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t-2 font-bold">
                                        <td colSpan={2} className="py-2">Totals</td>
                                        <td className="py-2 text-right">{format(totals.debit)}</td>
                                        <td className="py-2 text-right">{format(totals.credit)}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>

                            {!totals.balanced && totals.debit + totals.credit > 0 && (
                                <div className="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-400">
                                    ⚠️ Template is unbalanced. Difference: {format(Math.abs(totals.debit - totals.credit))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" asChild>
                            <Link href="/accounting/recurring-journal-entries">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing || !totals.balanced}>
                            {processing ? 'Saving...' : 'Create Recurring Entry'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

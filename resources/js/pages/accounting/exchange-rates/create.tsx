import { Head, useForm, Link } from '@inertiajs/react';
import ExchangeRateController from '@/actions/App/Http/Controllers/Accounting/ExchangeRateController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import { CURRENCIES } from '@/lib/currencies';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ExchangeRate } from '@/types';

type Props = {
    rate?: ExchangeRate;
};

export default function ExchangeRateForm({ rate }: Props) {
    const { currency: baseCurrency } = useCurrency();
    const isEditing = !!rate;
    const today = new Date().toISOString().split('T')[0];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Exchange Rates', href: ExchangeRateController.index.url() },
        { title: isEditing ? 'Edit Rate' : 'Add Rate', href: isEditing ? ExchangeRateController.edit.url(rate!.id) : ExchangeRateController.create.url() },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        currency_code: rate?.currency_code || '',
        rate: rate?.rate?.toString() || '',
        date: rate?.date || today,
    });

    const foreignCurrencies = CURRENCIES.filter((c) => c.code !== baseCurrency);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(ExchangeRateController.update.url(rate!.id));
        } else {
            post(ExchangeRateController.store.url());
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? 'Edit Exchange Rate' : 'Add Exchange Rate'} />
            <div className="max-w-lg mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{isEditing ? 'Edit Exchange Rate' : 'Add Exchange Rate'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="currency_code">Foreign Currency *</Label>
                                <Select value={data.currency_code} onValueChange={(v) => setData('currency_code', v)}>
                                    <SelectTrigger id="currency_code"><SelectValue placeholder="Select currency" /></SelectTrigger>
                                    <SelectContent>
                                        {foreignCurrencies.map((c) => (
                                            <SelectItem key={c.code} value={c.code}>{c.code} — {c.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.currency_code} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="rate">
                                    Rate{data.currency_code ? ` (1 ${data.currency_code} = ? ${baseCurrency})` : ''}
                                </Label>
                                <Input
                                    id="rate"
                                    type="number"
                                    step="0.000001"
                                    min="0.000001"
                                    placeholder="e.g. 1.085000"
                                    value={data.rate}
                                    onChange={(e) => setData('rate', e.target.value)}
                                />
                                <InputError message={errors.rate} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="date">Effective Date *</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={data.date}
                                    onChange={(e) => setData('date', e.target.value)}
                                />
                                <InputError message={errors.date} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href={ExchangeRateController.index.url()}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : isEditing ? 'Update Rate' : 'Save Rate'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

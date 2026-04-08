import { Head, Link, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import ExchangeRateController from '@/actions/App/Http/Controllers/Accounting/ExchangeRateController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ExchangeRate } from '@/types';

type PaginatedRates = {
    data: ExchangeRate[];
    current_page: number;
    last_page: number;
};

type Props = {
    rates: PaginatedRates;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exchange Rates', href: ExchangeRateController.index.url() },
];

export default function ExchangeRateIndex({ rates }: Props) {
    const { currency: baseCurrency } = useCurrency();

    function handleDelete(rate: ExchangeRate) {
        if (confirm(`Delete ${rate.currency_code} rate for ${rate.date}?`)) {
            router.delete(ExchangeRateController.destroy.url(rate.id));
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exchange Rates" />
            <div className="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Exchange Rates</h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            Rates relative to your base currency ({baseCurrency}). 1 foreign = X {baseCurrency}.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={ExchangeRateController.create.url()}>
                            <Plus className="mr-2 size-4" /> Add Rate
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {rates.data.length === 0 ? (
                            <div className="p-8 text-center text-muted-foreground">
                                <p>No exchange rates defined yet.</p>
                                <p className="text-sm mt-1">Add rates to enable multi-currency invoicing and payments.</p>
                            </div>
                        ) : (
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b text-sm text-muted-foreground">
                                        <th className="text-left px-4 py-3">Currency</th>
                                        <th className="text-right px-4 py-3">Rate (per 1 unit)</th>
                                        <th className="text-left px-4 py-3">Date</th>
                                        <th className="w-20 px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rates.data.map((rate) => (
                                        <tr key={rate.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{rate.currency_code}</td>
                                            <td className="px-4 py-3 text-right font-mono">
                                                {Number(rate.rate).toFixed(6)} {baseCurrency}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{rate.date}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1 justify-end">
                                                    <Button variant="ghost" size="icon" className="size-8" asChild>
                                                        <Link href={ExchangeRateController.edit.url(rate.id)}>
                                                            <Pencil className="size-3.5" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-muted-foreground hover:text-red-600"
                                                        onClick={() => handleDelete(rate)}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>

                {rates.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {Array.from({ length: rates.last_page }, (_, i) => i + 1).map((page) => (
                            <Button
                                key={page}
                                variant={page === rates.current_page ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => router.get(ExchangeRateController.index.url(), { page })}
                            >
                                {page}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

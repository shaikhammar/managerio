import { usePage } from '@inertiajs/react';
import { formatCurrency } from '@/lib/utils';

export function useCurrency() {
    const { currency } = usePage().props;

    return {
        currency: currency ?? 'USD',
        format: (amount: number | string) => formatCurrency(amount, currency ?? 'USD'),
    };
}

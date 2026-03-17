import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, TaxCode } from '@/types';

type Props = { taxCode?: TaxCode };

export default function TaxCodeForm({ taxCode }: Props) {
    const isEditing = !!taxCode;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Tax Codes', href: '/accounting/tax-codes' },
        { title: isEditing ? `Edit ${taxCode.name}` : 'New Tax Code', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        name: taxCode?.name || '',
        rate: taxCode?.rate?.toString() || '',
        description: taxCode?.description || '',
        is_active: taxCode?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEditing) {
            put(`/accounting/tax-codes/${taxCode!.id}`);
        } else {
            post('/accounting/tax-codes');
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Edit ${taxCode!.name}` : 'New Tax Code'} />
            <div className="max-w-md mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>{isEditing ? 'Edit Tax Code' : 'New Tax Code'}</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required placeholder="e.g. VAT, GST, Sales Tax" />
                                <InputError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="rate">Rate (%) *</Label>
                                <Input id="rate" type="number" step="0.01" min="0" max="100" value={data.rate} onChange={(e) => setData('rate', e.target.value)} required />
                                <InputError message={errors.rate} />
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
                                <Button variant="outline" type="button" asChild><Link href="/accounting/tax-codes">Cancel</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Saving...' : isEditing ? 'Save' : 'Create'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

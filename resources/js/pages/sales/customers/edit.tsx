import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Contact } from '@/types';

type Props = { customer: Contact };

export default function CustomerEdit({ customer }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Customers', href: '/sales/customers' },
        { title: customer.name, href: `/sales/customers/${customer.id}` },
        { title: 'Edit', href: `/sales/customers/${customer.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: customer.name,
        email: customer.email || '',
        phone: customer.phone || '',
        tax_number: customer.tax_number || '',
        address_line_1: customer.address_line_1 || '',
        address_line_2: customer.address_line_2 || '',
        city: customer.city || '',
        state: customer.state || '',
        postal_code: customer.postal_code || '',
        country: customer.country || '',
        notes: customer.notes || '',
        is_active: customer.is_active,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/sales/customers/${customer.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${customer.name}`} />
            <div className="max-w-2xl mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Customer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tax_number">Tax Number</Label>
                                <Input id="tax_number" value={data.tax_number} onChange={(e) => setData('tax_number', e.target.value)} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="address_line_1">Address Line 1</Label>
                                <Input id="address_line_1" value={data.address_line_1} onChange={(e) => setData('address_line_1', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="address_line_2">Address Line 2</Label>
                                <Input id="address_line_2" value={data.address_line_2} onChange={(e) => setData('address_line_2', e.target.value)} />
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="city">City</Label>
                                    <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="state">State</Label>
                                    <Input id="state" value={data.state} onChange={(e) => setData('state', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="postal_code">Postal Code</Label>
                                    <Input id="postal_code" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/sales/customers">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

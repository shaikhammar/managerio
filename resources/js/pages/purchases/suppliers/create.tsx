import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Suppliers', href: '/purchases/suppliers' },
    { title: 'New Supplier', href: '/purchases/suppliers/create' },
];

export default function SupplierCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '', email: '', phone: '', tax_number: '',
        address_line_1: '', address_line_2: '', city: '', state: '', postal_code: '', country: '', notes: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/purchases/suppliers');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Supplier" />
            <div className="max-w-2xl mx-auto p-4 md:p-6">
                <Card>
                    <CardHeader><CardTitle>New Supplier</CardTitle></CardHeader>
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
                                <Label htmlFor="address_line_1">Address</Label>
                                <Input id="address_line_1" value={data.address_line_1} onChange={(e) => setData('address_line_1', e.target.value)} />
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2"><Label htmlFor="city">City</Label><Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} /></div>
                                <div className="space-y-2"><Label htmlFor="state">State</Label><Input id="state" value={data.state} onChange={(e) => setData('state', e.target.value)} /></div>
                                <div className="space-y-2"><Label htmlFor="postal_code">Postal Code</Label><Input id="postal_code" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} /></div>
                            </div>
                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild><Link href="/purchases/suppliers">Cancel</Link></Button>
                                <Button type="submit" disabled={processing}>{processing ? 'Creating...' : 'Create Supplier'}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, ShoppingCart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Contact } from '@/types';

type Props = { supplier: Contact };

export default function SupplierShow({ supplier }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Suppliers', href: '/purchases/suppliers' },
        { title: supplier.name, href: `/purchases/suppliers/${supplier.id}` },
    ];

    function handleDelete() {
        if (confirm(`Delete supplier "${supplier.name}"? This cannot be undone.`)) {
            router.delete(`/purchases/suppliers/${supplier.id}`);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={supplier.name} />
            <div className="max-w-2xl mx-auto p-4 md:p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/purchases/suppliers"><ArrowLeft className="size-4" /></Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">{supplier.name}</h1>
                            <p className="text-sm text-muted-foreground capitalize">{supplier.type}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/purchases/suppliers/${supplier.id}/edit`}>
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/purchases/invoices/create?contact_id=${supplier.id}`}>
                                <ShoppingCart className="mr-2 size-4" />
                                New Purchase Invoice
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader><CardTitle>Contact Details</CardTitle></CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        {supplier.email && (
                            <div className="flex gap-4">
                                <span className="w-32 text-muted-foreground">Email</span>
                                <a href={`mailto:${supplier.email}`} className="text-primary hover:underline">{supplier.email}</a>
                            </div>
                        )}
                        {supplier.phone && (
                            <div className="flex gap-4">
                                <span className="w-32 text-muted-foreground">Phone</span>
                                <span>{supplier.phone}</span>
                            </div>
                        )}
                        {supplier.tax_number && (
                            <div className="flex gap-4">
                                <span className="w-32 text-muted-foreground">Tax Number</span>
                                <span>{supplier.tax_number}</span>
                            </div>
                        )}
                        {supplier.address_line_1 && (
                            <div className="flex gap-4">
                                <span className="w-32 text-muted-foreground">Address</span>
                                <div>
                                    <p>{supplier.address_line_1}</p>
                                    {supplier.address_line_2 && <p>{supplier.address_line_2}</p>}
                                    <p>{[supplier.city, supplier.state, supplier.postal_code].filter(Boolean).join(', ')}</p>
                                    {supplier.country && <p>{supplier.country}</p>}
                                </div>
                            </div>
                        )}
                        {supplier.notes && (
                            <div className="flex gap-4">
                                <span className="w-32 text-muted-foreground">Notes</span>
                                <p className="whitespace-pre-wrap">{supplier.notes}</p>
                            </div>
                        )}
                        <div className="flex gap-4">
                            <span className="w-32 text-muted-foreground">Status</span>
                            <span className={supplier.is_active ? 'text-emerald-600' : 'text-muted-foreground'}>
                                {supplier.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button variant="destructive" size="sm" onClick={handleDelete}>
                        Delete Supplier
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

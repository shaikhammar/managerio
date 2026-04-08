import { Head, Link } from '@inertiajs/react';
import { Mail, Phone, MapPin, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Contact, Invoice } from '@/types';

type Props = {
    customer: Contact & { invoices?: Invoice[] };
};

export default function CustomerShow({ customer }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Customers', href: '/sales/customers' },
        { title: customer.name, href: `/sales/customers/${customer.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={customer.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">{customer.name}</h1>
                            <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                                {customer.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/sales/customers/${customer.id}/edit`}>Edit</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`/sales/invoices/create?contact_id=${customer.id}`}>New Invoice</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Contact Info */}
                    <Card>
                        <CardHeader><CardTitle className="text-base">Contact Details</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {customer.email && (
                                <div className="flex items-center gap-2 text-sm">
                                    <Mail className="size-4 text-muted-foreground" />
                                    <a href={`mailto:${customer.email}`} className="hover:underline">{customer.email}</a>
                                </div>
                            )}
                            {customer.phone && (
                                <div className="flex items-center gap-2 text-sm">
                                    <Phone className="size-4 text-muted-foreground" />
                                    {customer.phone}
                                </div>
                            )}
                            {customer.address_line_1 && (
                                <div className="flex items-start gap-2 text-sm">
                                    <MapPin className="size-4 text-muted-foreground mt-0.5" />
                                    <div>
                                        <p>{customer.address_line_1}</p>
                                        {customer.address_line_2 && <p>{customer.address_line_2}</p>}
                                        <p>{[customer.city, customer.state, customer.postal_code].filter(Boolean).join(', ')}</p>
                                    </div>
                                </div>
                            )}
                            {customer.tax_number && (
                                <div className="flex items-center gap-2 text-sm">
                                    <FileText className="size-4 text-muted-foreground" />
                                    Tax: {customer.tax_number}
                                </div>
                            )}
                            {!customer.email && !customer.phone && !customer.address_line_1 && (
                                <p className="text-sm text-muted-foreground">No contact details provided</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    <Card>
                        <CardHeader><CardTitle className="text-base">Notes</CardTitle></CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                                {customer.notes || 'No notes'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Invoices */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Recent Invoices</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {!customer.invoices || customer.invoices.length === 0 ? (
                            <p className="text-sm text-muted-foreground py-4 text-center">No invoices yet</p>
                        ) : (
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left py-2 text-sm font-medium text-muted-foreground">Number</th>
                                        <th className="text-left py-2 text-sm font-medium text-muted-foreground">Date</th>
                                        <th className="text-left py-2 text-sm font-medium text-muted-foreground">Status</th>
                                        <th className="text-right py-2 text-sm font-medium text-muted-foreground">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {customer.invoices.map((inv) => (
                                        <tr key={inv.id} className="border-b last:border-0">
                                            <td className="py-2">
                                                <Link href={`/sales/invoices/${inv.id}`} className="font-mono text-sm hover:underline">{inv.number}</Link>
                                            </td>
                                            <td className="py-2 text-sm text-muted-foreground">{inv.date}</td>
                                            <td className="py-2"><Badge variant="secondary" className="capitalize text-xs">{inv.status}</Badge></td>
                                            <td className="py-2 text-right text-sm font-medium">{formatCurrency(inv.total, inv.currency_code)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

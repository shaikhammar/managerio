import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, BillingUnit, ServiceType } from '@/types';

type BillingUnitOption = { value: string; label: string };

type Props = {
    serviceType?: ServiceType;
    billingUnits: BillingUnitOption[];
};

export default function ServiceTypeForm({ serviceType, billingUnits }: Props) {
    const isEditing = !!serviceType;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Service Types', href: '/translation/service-types' },
        { title: isEditing ? `Edit ${serviceType.name}` : 'New Service Type', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        name: serviceType?.name || '',
        code: serviceType?.code || '',
        description: serviceType?.description || '',
        default_unit: (serviceType?.default_unit || 'word') as BillingUnit,
        is_active: serviceType?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(`/translation/service-types/${serviceType!.id}`);
        } else {
            post('/translation/service-types');
        }
    }

    function handleNameChange(name: string) {
        setData((prev) => ({
            ...prev,
            name,
            code: prev.code || name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, ''),
        }));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Edit ${serviceType!.name}` : 'New Service Type'} />
            <div className="mx-auto max-w-md p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{isEditing ? 'Edit Service Type' : 'New Service Type'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => handleNameChange(e.target.value)}
                                    required
                                    placeholder="e.g. Translation, Editing, Proofreading"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="code">Code *</Label>
                                <Input
                                    id="code"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value)}
                                    required
                                    placeholder="e.g. translation, editing"
                                    className="font-mono"
                                />
                                <InputError message={errors.code} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="default_unit">Default Billing Unit *</Label>
                                <Select
                                    value={data.default_unit}
                                    onValueChange={(v) => setData('default_unit', v as BillingUnit)}
                                >
                                    <SelectTrigger id="default_unit">
                                        <SelectValue placeholder="Select billing unit" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {billingUnits.map((unit) => (
                                            <SelectItem key={unit.value} value={unit.value}>
                                                {unit.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.default_unit} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Brief description of this service type"
                                    rows={3}
                                />
                                <InputError message={errors.description} />
                            </div>
                            {isEditing && (
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(c) => setData('is_active', c as boolean)}
                                    />
                                    <Label htmlFor="is_active">Active</Label>
                                </div>
                            )}
                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/service-types">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : isEditing ? 'Save' : 'Create'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

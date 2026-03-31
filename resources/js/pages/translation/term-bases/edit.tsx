import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, TermBase } from '@/types';

type ContactOption = { id: number; name: string };

type Props = {
    termBase: TermBase;
    customers: ContactOption[];
    subjectFields: string[];
};

export default function TermBaseEdit({ termBase, customers, subjectFields }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Term Bases', href: '/translation/term-bases' },
        { title: `Edit ${termBase.name}`, href: '#' },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: termBase.name,
        contact_id: termBase.contact_id ? String(termBase.contact_id) : '',
        subject_field: termBase.subject_field || '',
        description: termBase.description || '',
        notes: termBase.notes || '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/translation/term-bases/${termBase.id}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${termBase.name}`} />
            <div className="mx-auto max-w-lg p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Term Base</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="contact_id">Client (optional)</Label>
                                <Select value={data.contact_id || 'none'} onValueChange={(v) => setData('contact_id', v === 'none' ? '' : v)}>
                                    <SelectTrigger id="contact_id">
                                        <SelectValue placeholder="Business-wide term base" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Business-wide term base</SelectItem>
                                        {customers.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.contact_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="subject_field">Subject Field</Label>
                                <Select
                                    value={data.subject_field || 'none'}
                                    onValueChange={(v) => setData('subject_field', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger id="subject_field">
                                        <SelectValue placeholder="Select subject…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Not specified</SelectItem>
                                        {subjectFields.map((sf) => (
                                            <SelectItem key={sf} value={sf}>
                                                {sf}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.subject_field} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={2}
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/term-bases">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving…' : 'Save'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ContactOption = { id: number; name: string };

type Props = {
    customers: ContactOption[];
    subjectFields: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Term Bases', href: '/translation/term-bases' },
    { title: 'New Term Base', href: '#' },
];

export default function TermBaseCreate({ customers, subjectFields }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        contact_id: '',
        subject_field: '',
        description: '',
        notes: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/translation/term-bases');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Term Base" />
            <div className="mx-auto max-w-lg p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>New Term Base</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Acme Corp Marketing Glossary"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="contact_id">Client (optional)</Label>
                                <Select value={data.contact_id} onValueChange={(v) => setData('contact_id', v === 'none' ? '' : v)}>
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
                                <Select value={data.subject_field} onValueChange={(v) => setData('subject_field', v === 'none' ? '' : v)}>
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
                                    placeholder="What this term base covers…"
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
                                    placeholder="Optional notes…"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/term-bases">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating…' : 'Create Term Base'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

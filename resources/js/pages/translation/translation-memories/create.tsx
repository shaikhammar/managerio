import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, LanguageOption } from '@/types';

const SOFTWARE_OPTIONS = ['Trados Studio', 'memoQ', 'Phrase (Memsource)', 'Wordfast', 'DejaVu', 'Across', 'OmegaT', 'Other'];

type ContactOption = { id: number; name: string };

type Props = {
    languages: LanguageOption[];
    customers: ContactOption[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Memories', href: '/translation/translation-memories' },
    { title: 'New TM', href: '#' },
];

export default function TranslationMemoryCreate({ languages, customers }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        source_language_id: '',
        target_language_id: '',
        contact_id: '',
        software: '',
        notes: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/translation/translation-memories');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Translation Memory" />
            <div className="mx-auto max-w-lg p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>New Translation Memory</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Acme Corp Legal EN-DE TM"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="source_language_id">Source Language *</Label>
                                    <Select value={data.source_language_id} onValueChange={(v) => setData('source_language_id', v)}>
                                        <SelectTrigger id="source_language_id">
                                            <SelectValue placeholder="Select…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languages.map((l) => (
                                                <SelectItem key={l.id} value={String(l.id)}>
                                                    {l.code.toUpperCase()} — {l.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.source_language_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="target_language_id">Target Language *</Label>
                                    <Select value={data.target_language_id} onValueChange={(v) => setData('target_language_id', v)}>
                                        <SelectTrigger id="target_language_id">
                                            <SelectValue placeholder="Select…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languages.map((l) => (
                                                <SelectItem key={l.id} value={String(l.id)}>
                                                    {l.code.toUpperCase()} — {l.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.target_language_id} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="contact_id">Client (optional)</Label>
                                <Select value={data.contact_id} onValueChange={(v) => setData('contact_id', v === 'none' ? '' : v)}>
                                    <SelectTrigger id="contact_id">
                                        <SelectValue placeholder="Business-wide TM" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Business-wide TM</SelectItem>
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
                                <Label htmlFor="software">CAT Software</Label>
                                <Select value={data.software} onValueChange={(v) => setData('software', v === 'none' ? '' : v)}>
                                    <SelectTrigger id="software">
                                        <SelectValue placeholder="Select CAT tool…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Not specified</SelectItem>
                                        {SOFTWARE_OPTIONS.map((s) => (
                                            <SelectItem key={s} value={s}>
                                                {s}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.software} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={3}
                                    placeholder="Optional notes…"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/translation-memories">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating…' : 'Create TM'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

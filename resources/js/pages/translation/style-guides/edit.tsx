import { Head, Link, useForm } from '@inertiajs/react';
import { Download } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, StyleGuide } from '@/types';

type ContactOption = { id: number; name: string };

type Props = {
    styleGuide: StyleGuide;
    customers: ContactOption[];
};

export default function StyleGuideEdit({ styleGuide, customers }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Style Guides', href: '/translation/style-guides' },
        { title: `Edit ${styleGuide.name}`, href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        contact_id: string;
        description: string;
        file: File | null;
        _method: string;
    }>({
        name: styleGuide.name,
        contact_id: styleGuide.contact_id ? String(styleGuide.contact_id) : '',
        description: styleGuide.description || '',
        file: null,
        _method: 'PUT',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/translation/style-guides/${styleGuide.id}`, { forceFormData: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${styleGuide.name}`} />
            <div className="mx-auto max-w-lg p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Style Guide</CardTitle>
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
                                        <SelectValue placeholder="Business-wide style guide" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Business-wide style guide</SelectItem>
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
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                />
                                <InputError message={errors.description} />
                            </div>

                            {styleGuide.file_path && (
                                <div className="bg-muted/50 rounded-md p-3 text-sm">
                                    <p className="text-muted-foreground mb-1">Current file:</p>
                                    <a
                                        href={`/storage/${styleGuide.file_path}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary inline-flex items-center gap-1 hover:underline"
                                    >
                                        <Download className="size-3" />
                                        {styleGuide.file_name}
                                    </a>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="file">
                                    {styleGuide.file_path ? 'Replace File' : 'Upload File'} (PDF, DOC, DOCX, TXT — max 10 MB)
                                </Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.txt"
                                    onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                                />
                                <InputError message={errors.file} />
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/style-guides">Cancel</Link>
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

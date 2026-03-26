import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Language } from '@/types';

type Props = { language?: Language };

export default function LanguageForm({ language }: Props) {
    const isEditing = !!language;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Languages', href: '/translation/languages' },
        { title: isEditing ? `Edit ${language.name}` : 'New Language', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        code: language?.code || '',
        name: language?.name || '',
        native_name: language?.native_name || '',
        is_active: language?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(`/translation/languages/${language!.id}`);
        } else {
            post('/translation/languages');
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Edit ${language!.name}` : 'New Language'} />
            <div className="mx-auto max-w-md p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{isEditing ? 'Edit Language' : 'New Language'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="code">ISO Code *</Label>
                                <Input
                                    id="code"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value)}
                                    required
                                    placeholder="e.g. en, fr, de, zh-CN"
                                    className="font-mono"
                                />
                                <InputError message={errors.code} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    placeholder="e.g. English, French, German"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="native_name">Native Name</Label>
                                <Input
                                    id="native_name"
                                    value={data.native_name}
                                    onChange={(e) => setData('native_name', e.target.value)}
                                    placeholder="e.g. English, Français, Deutsch"
                                />
                                <InputError message={errors.native_name} />
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
                                    <Link href="/translation/languages">Cancel</Link>
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

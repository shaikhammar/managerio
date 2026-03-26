import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, LanguageOption, LanguagePair } from '@/types';

type Props = {
    pair?: LanguagePair;
    languages: LanguageOption[];
};

export default function LanguagePairForm({ pair, languages }: Props) {
    const isEditing = !!pair;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Language Pairs', href: '/translation/language-pairs' },
        { title: isEditing ? 'Edit Language Pair' : 'New Language Pair', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        source_language_id: pair?.source_language_id?.toString() || '',
        target_language_id: pair?.target_language_id?.toString() || '',
        is_active: pair?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(`/translation/language-pairs/${pair!.id}`);
        } else {
            post('/translation/language-pairs');
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? 'Edit Language Pair' : 'New Language Pair'} />
            <div className="mx-auto max-w-md p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{isEditing ? 'Edit Language Pair' : 'New Language Pair'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="source_language_id">Source Language *</Label>
                                <Select
                                    value={data.source_language_id}
                                    onValueChange={(v) => setData('source_language_id', v)}
                                >
                                    <SelectTrigger id="source_language_id">
                                        <SelectValue placeholder="Select source language" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {languages.map((lang) => (
                                            <SelectItem key={lang.id} value={lang.id.toString()}>
                                                <span className="font-mono text-xs text-muted-foreground mr-2">{lang.code}</span>
                                                {lang.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.source_language_id} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="target_language_id">Target Language *</Label>
                                <Select
                                    value={data.target_language_id}
                                    onValueChange={(v) => setData('target_language_id', v)}
                                >
                                    <SelectTrigger id="target_language_id">
                                        <SelectValue placeholder="Select target language" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {languages.map((lang) => (
                                            <SelectItem key={lang.id} value={lang.id.toString()}>
                                                <span className="font-mono text-xs text-muted-foreground mr-2">{lang.code}</span>
                                                {lang.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.target_language_id} />
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
                                    <Link href="/translation/language-pairs">Cancel</Link>
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

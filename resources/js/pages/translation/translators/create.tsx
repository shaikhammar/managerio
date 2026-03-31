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
import type { BreadcrumbItem, LanguagePair, ServiceType, TranslatorAvailability, TranslatorCertification, TranslatorProfile, TranslatorSpecialisation } from '@/types';

type Option = { value: string; label: string };
type ContactOption = { id: number; name: string };
type ServiceTypeOption = Pick<ServiceType, 'id' | 'name'>;

type Props = {
    translator?: TranslatorProfile;
    contacts: ContactOption[];
    languagePairs: LanguagePair[];
    serviceTypes: ServiceTypeOption[];
    availabilities: Option[];
    specialisations: Option[];
    catTools: Option[];
    certifications: Option[];
};

export default function TranslatorProfileForm({
    translator,
    contacts,
    languagePairs,
    serviceTypes,
    availabilities,
    specialisations,
    catTools,
    certifications,
}: Props) {
    const isEditing = !!translator;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Translators', href: '/translation/translators' },
        { title: isEditing ? 'Edit Profile' : 'New Profile', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm<{
        contact_id: number | '';
        availability: TranslatorAvailability;
        quality_rating: number | '';
        quality_notes: string;
        weekly_capacity: number | '';
        specialisations: string[];
        cat_tools: string[];
        certifications: string[];
        language_pair_ids: number[];
        service_type_ids: number[];
    }>({
        contact_id: translator?.contact_id ?? '',
        availability: (translator?.availability as TranslatorAvailability) ?? 'available',
        quality_rating: translator?.quality_rating ?? '',
        quality_notes: translator?.quality_notes ?? '',
        weekly_capacity: (translator as any)?.weekly_capacity ?? '',
        specialisations: (translator?.specialisations as string[]) ?? [],
        cat_tools: (translator?.cat_tools as string[]) ?? [],
        certifications: (translator?.certifications as string[]) ?? [],
        language_pair_ids: translator?.language_pairs?.map((lp) => lp.id) ?? [],
        service_type_ids: translator?.service_types?.map((st) => st.id) ?? [],
    });

    function toggleArrayValue<T extends string>(field: keyof typeof data, value: T) {
        const current = (data[field] as T[]) ?? [];
        const updated = current.includes(value) ? current.filter((v) => v !== value) : [...current, value];
        setData(field, updated as never);
    }

    function toggleIdValue(field: 'language_pair_ids' | 'service_type_ids', id: number) {
        const current = (data[field] ?? []) as number[];
        const updated = current.includes(id) ? current.filter((v) => v !== id) : [...current, id];
        setData(field, updated);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(`/translation/translators/${translator.id}`);
        } else {
            post('/translation/translators');
        }
    }

    function pairLabel(lp: LanguagePair) {
        const src = lp.source_language?.code ?? '?';
        const tgt = lp.target_language?.code ?? '?';
        const srcName = lp.source_language?.name ?? '';
        const tgtName = lp.target_language?.name ?? '';

        return `${src} → ${tgt} (${srcName} → ${tgtName})`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? 'Edit Translator Profile' : 'New Translator Profile'} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        {isEditing ? 'Edit Translator Profile' : 'New Translator Profile'}
                    </h1>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    {/* Basic Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="contact_id">Supplier Contact *</Label>
                                <Select
                                    value={data.contact_id ? String(data.contact_id) : ''}
                                    onValueChange={(v) => setData('contact_id', Number(v))}
                                    disabled={isEditing}
                                >
                                    <SelectTrigger id="contact_id">
                                        <SelectValue placeholder="Select a supplier…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {contacts.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.contact_id} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="availability">Availability *</Label>
                                <Select
                                    value={data.availability}
                                    onValueChange={(v) => setData('availability', v as TranslatorAvailability)}
                                >
                                    <SelectTrigger id="availability">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availabilities.map((a) => (
                                            <SelectItem key={a.value} value={a.value}>
                                                {a.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.availability} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="quality_rating">Quality Rating (1–5)</Label>
                                <Select
                                    value={data.quality_rating ? String(data.quality_rating) : 'none'}
                                    onValueChange={(v) => setData('quality_rating', v === 'none' ? '' : Number(v))}
                                >
                                    <SelectTrigger id="quality_rating">
                                        <SelectValue placeholder="No rating" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No rating</SelectItem>
                                        {[1, 2, 3, 4, 5].map((n) => (
                                            <SelectItem key={n} value={String(n)}>
                                                {'★'.repeat(n)} ({n})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.quality_rating} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="weekly_capacity">Weekly Capacity (words)</Label>
                                <Input
                                    id="weekly_capacity"
                                    type="number"
                                    min={0}
                                    value={data.weekly_capacity === '' ? '' : String(data.weekly_capacity)}
                                    onChange={(e) => setData('weekly_capacity', e.target.value === '' ? '' : Number(e.target.value))}
                                    placeholder="e.g. 5000"
                                />
                                <InputError message={errors.weekly_capacity} />
                            </div>

                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <Label htmlFor="quality_notes">Quality Notes</Label>
                                <Textarea
                                    id="quality_notes"
                                    value={data.quality_notes}
                                    onChange={(e) => setData('quality_notes', e.target.value)}
                                    rows={3}
                                    placeholder="Internal notes about this translator's quality…"
                                />
                                <InputError message={errors.quality_notes} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Language Pairs */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Language Pairs</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {languagePairs.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No active language pairs found.{' '}
                                    <Link href="/translation/language-pairs/create" className="underline">
                                        Create one
                                    </Link>
                                    .
                                </p>
                            ) : (
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {languagePairs.map((lp) => (
                                        <div key={lp.id} className="flex items-center gap-2">
                                            <Checkbox
                                                id={`lp-${lp.id}`}
                                                checked={data.language_pair_ids.includes(lp.id)}
                                                onCheckedChange={() => toggleIdValue('language_pair_ids', lp.id)}
                                            />
                                            <Label htmlFor={`lp-${lp.id}`} className="cursor-pointer font-normal">
                                                {pairLabel(lp)}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Service Types */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Services</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {serviceTypes.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No active service types found.</p>
                            ) : (
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {serviceTypes.map((st) => (
                                        <div key={st.id} className="flex items-center gap-2">
                                            <Checkbox
                                                id={`st-${st.id}`}
                                                checked={data.service_type_ids.includes(st.id)}
                                                onCheckedChange={() => toggleIdValue('service_type_ids', st.id)}
                                            />
                                            <Label htmlFor={`st-${st.id}`} className="cursor-pointer font-normal">
                                                {st.name}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Specialisations */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Specialisations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {specialisations.map((s) => (
                                    <div key={s.value} className="flex items-center gap-2">
                                        <Checkbox
                                            id={`spec-${s.value}`}
                                            checked={(data.specialisations as string[]).includes(s.value)}
                                            onCheckedChange={() => toggleArrayValue<TranslatorSpecialisation>('specialisations', s.value as TranslatorSpecialisation)}
                                        />
                                        <Label htmlFor={`spec-${s.value}`} className="cursor-pointer font-normal">
                                            {s.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* CAT Tools */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">CAT Tools</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {catTools.map((t) => (
                                    <div key={t.value} className="flex items-center gap-2">
                                        <Checkbox
                                            id={`cat-${t.value}`}
                                            checked={(data.cat_tools as string[]).includes(t.value)}
                                            onCheckedChange={() => toggleArrayValue('cat_tools', t.value)}
                                        />
                                        <Label htmlFor={`cat-${t.value}`} className="cursor-pointer font-normal">
                                            {t.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Certifications */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Certifications</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {certifications.map((c) => (
                                    <div key={c.value} className="flex items-center gap-2">
                                        <Checkbox
                                            id={`cert-${c.value}`}
                                            checked={(data.certifications as string[]).includes(c.value)}
                                            onCheckedChange={() => toggleArrayValue<TranslatorCertification>('certifications', c.value as TranslatorCertification)}
                                        />
                                        <Label htmlFor={`cert-${c.value}`} className="cursor-pointer font-normal">
                                            {c.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : isEditing ? 'Update Profile' : 'Create Profile'}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/translation/translators">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

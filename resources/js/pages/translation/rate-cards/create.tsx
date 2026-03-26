import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BillingUnit, BreadcrumbItem, LanguagePair, RateCard, RateCardType, ServiceType } from '@/types';

type Option = { value: string; label: string };
type ContactOption = { id: number; name: string; type: string };
type ServiceTypeOption = Pick<ServiceType, 'id' | 'name' | 'default_unit'>;
type VolumeTierInput = { minimum_words: number | ''; unit_rate: number | '' };

type Props = {
    rateCard?: RateCard;
    languagePairs: LanguagePair[];
    serviceTypes: ServiceTypeOption[];
    contacts: ContactOption[];
    rateCardTypes: Option[];
    billingUnits: Option[];
};

export default function RateCardForm({ rateCard, languagePairs, serviceTypes, contacts, rateCardTypes, billingUnits }: Props) {
    const isEditing = !!rateCard;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Rate Cards', href: '/translation/rate-cards' },
        { title: isEditing ? 'Edit Rate Card' : 'New Rate Card', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm<{
        type: RateCardType;
        contact_id: number | '';
        language_pair_id: number | '';
        service_type_id: number | '';
        unit_rate: number | '';
        unit: BillingUnit;
        minimum_fee: number | '';
        rush_multiplier: number | '';
        rush_fixed_surcharge: number | '';
        notes: string;
        is_active: boolean;
        volume_tiers: VolumeTierInput[];
    }>({
        type: (rateCard?.type as RateCardType) || 'default',
        contact_id: rateCard?.contact_id ?? '',
        language_pair_id: rateCard?.language_pair_id ?? '',
        service_type_id: rateCard?.service_type_id ?? '',
        unit_rate: rateCard ? parseFloat(rateCard.unit_rate) : '',
        unit: (rateCard?.unit as BillingUnit) || 'word',
        minimum_fee: rateCard?.minimum_fee ? parseFloat(rateCard.minimum_fee) : '',
        rush_multiplier: rateCard?.rush_multiplier ? parseFloat(rateCard.rush_multiplier) : '',
        rush_fixed_surcharge: rateCard?.rush_fixed_surcharge ? parseFloat(rateCard.rush_fixed_surcharge) : '',
        notes: rateCard?.notes || '',
        is_active: rateCard?.is_active ?? true,
        volume_tiers: rateCard?.volume_tiers?.map((t) => ({ minimum_words: t.minimum_words, unit_rate: parseFloat(t.unit_rate) })) || [],
    });

    // Auto-set unit from selected service type's default unit
    useEffect(() => {
        if (!data.service_type_id || isEditing) return;
        const st = serviceTypes.find((s) => s.id === data.service_type_id);
        if (st) setData('unit', st.default_unit as BillingUnit);
    }, [data.service_type_id]);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEditing) {
            put(`/translation/rate-cards/${rateCard!.id}`);
        } else {
            post('/translation/rate-cards');
        }
    }

    function addVolumeTier() {
        setData('volume_tiers', [...data.volume_tiers, { minimum_words: '', unit_rate: '' }]);
    }

    function removeVolumeTier(index: number) {
        setData('volume_tiers', data.volume_tiers.filter((_, i) => i !== index));
    }

    function updateVolumeTier(index: number, field: keyof VolumeTierInput, value: number | '') {
        const updated = data.volume_tiers.map((tier, i) => (i === index ? { ...tier, [field]: value } : tier));
        setData('volume_tiers', updated);
    }

    const needsContact = data.type !== 'default';

    const filteredContacts = contacts.filter((c) => {
        if (data.type === 'client') return c.type === 'customer' || c.type === 'both';
        if (data.type === 'translator') return c.type === 'supplier' || c.type === 'both';
        return true;
    });

    function pairLabel(lp: LanguagePair) {
        const src = lp.source_language;
        const tgt = lp.target_language;
        if (!src || !tgt) return `Pair #${lp.id}`;
        return `${src.name} (${src.code}) → ${tgt.name} (${tgt.code})`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? 'Edit Rate Card' : 'New Rate Card'} />
            <div className="mx-auto max-w-2xl p-4 md:p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{isEditing ? 'Edit Rate Card' : 'New Rate Card'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-5">
                            {/* Type */}
                            <div className="space-y-2">
                                <Label htmlFor="type">Type *</Label>
                                <Select value={data.type} onValueChange={(v) => { setData('type', v as RateCardType); setData('contact_id', ''); }}>
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rateCardTypes.map((t) => (
                                            <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-muted-foreground text-xs">
                                    {data.type === 'default' && 'Business-wide base rates — apply when no client or translator rate exists.'}
                                    {data.type === 'client' && 'What you charge this client — overrides default rates.'}
                                    {data.type === 'translator' && 'What you pay this translator — used for cost calculations and POs.'}
                                </p>
                                <InputError message={errors.type} />
                            </div>

                            {/* Contact (hidden for default) */}
                            {needsContact && (
                                <div className="space-y-2">
                                    <Label htmlFor="contact_id">{data.type === 'client' ? 'Client' : 'Translator'} *</Label>
                                    <Select
                                        value={data.contact_id ? String(data.contact_id) : ''}
                                        onValueChange={(v) => setData('contact_id', v ? Number(v) : '')}
                                    >
                                        <SelectTrigger id="contact_id">
                                            <SelectValue placeholder={`Select ${data.type === 'client' ? 'client' : 'translator'}`} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredContacts.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.contact_id} />
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                {/* Language Pair */}
                                <div className="space-y-2">
                                    <Label htmlFor="language_pair_id">Language Pair *</Label>
                                    <Select
                                        value={data.language_pair_id ? String(data.language_pair_id) : ''}
                                        onValueChange={(v) => setData('language_pair_id', v ? Number(v) : '')}
                                    >
                                        <SelectTrigger id="language_pair_id">
                                            <SelectValue placeholder="Select pair" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languagePairs.map((lp) => (
                                                <SelectItem key={lp.id} value={String(lp.id)}>{pairLabel(lp)}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.language_pair_id} />
                                </div>

                                {/* Service Type */}
                                <div className="space-y-2">
                                    <Label htmlFor="service_type_id">Service Type *</Label>
                                    <Select
                                        value={data.service_type_id ? String(data.service_type_id) : ''}
                                        onValueChange={(v) => setData('service_type_id', v ? Number(v) : '')}
                                    >
                                        <SelectTrigger id="service_type_id">
                                            <SelectValue placeholder="Select service" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {serviceTypes.map((s) => (
                                                <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.service_type_id} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                {/* Unit Rate */}
                                <div className="space-y-2">
                                    <Label htmlFor="unit_rate">Unit Rate *</Label>
                                    <Input
                                        id="unit_rate"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        value={data.unit_rate}
                                        onChange={(e) => setData('unit_rate', e.target.value ? parseFloat(e.target.value) : '')}
                                        placeholder="0.1200"
                                    />
                                    <InputError message={errors.unit_rate} />
                                </div>

                                {/* Billing Unit */}
                                <div className="space-y-2">
                                    <Label htmlFor="unit">Billing Unit *</Label>
                                    <Select value={data.unit} onValueChange={(v) => setData('unit', v as BillingUnit)}>
                                        <SelectTrigger id="unit">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {billingUnits.map((u) => (
                                                <SelectItem key={u.value} value={u.value}>{u.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.unit} />
                                </div>
                            </div>

                            <Separator />

                            {/* Optional pricing fields */}
                            <p className="text-muted-foreground text-sm font-medium">Optional Pricing Rules</p>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="minimum_fee">Minimum Fee</Label>
                                    <Input
                                        id="minimum_fee"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.minimum_fee}
                                        onChange={(e) => setData('minimum_fee', e.target.value ? parseFloat(e.target.value) : '')}
                                        placeholder="e.g. 25.00"
                                    />
                                    <InputError message={errors.minimum_fee} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="rush_multiplier">Rush Multiplier</Label>
                                    <Input
                                        id="rush_multiplier"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        max="10"
                                        value={data.rush_multiplier}
                                        onChange={(e) => setData('rush_multiplier', e.target.value ? parseFloat(e.target.value) : '')}
                                        placeholder="e.g. 1.50"
                                    />
                                    <p className="text-muted-foreground text-xs">1.5 = 50% surcharge</p>
                                    <InputError message={errors.rush_multiplier} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="rush_fixed_surcharge">Rush Fixed Surcharge</Label>
                                    <Input
                                        id="rush_fixed_surcharge"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.rush_fixed_surcharge}
                                        onChange={(e) => setData('rush_fixed_surcharge', e.target.value ? parseFloat(e.target.value) : '')}
                                        placeholder="e.g. 50.00"
                                    />
                                    <InputError message={errors.rush_fixed_surcharge} />
                                </div>
                            </div>

                            {/* Notes */}
                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Internal notes about this rate"
                                    rows={2}
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <Separator />

                            {/* Volume Tiers */}
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium">Volume Tiers</p>
                                        <p className="text-muted-foreground text-xs">Lower rates above word count thresholds</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" onClick={addVolumeTier}>
                                        <Plus className="mr-1 size-3.5" />
                                        Add Tier
                                    </Button>
                                </div>

                                {data.volume_tiers.length > 0 && (
                                    <div className="space-y-2">
                                        <div className="grid grid-cols-[1fr_1fr_auto] gap-2 text-xs text-muted-foreground px-1">
                                            <span>Min. Words</span>
                                            <span>Rate per {data.unit}</span>
                                            <span />
                                        </div>
                                        {data.volume_tiers.map((tier, i) => (
                                            <div key={i} className="grid grid-cols-[1fr_1fr_auto] gap-2 items-start">
                                                <div>
                                                    <Input
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        value={tier.minimum_words}
                                                        onChange={(e) => updateVolumeTier(i, 'minimum_words', e.target.value ? parseInt(e.target.value) : '')}
                                                        placeholder="e.g. 10000"
                                                    />
                                                    <InputError message={(errors as Record<string, string>)[`volume_tiers.${i}.minimum_words`]} />
                                                </div>
                                                <div>
                                                    <Input
                                                        type="number"
                                                        step="0.0001"
                                                        min="0"
                                                        value={tier.unit_rate}
                                                        onChange={(e) => updateVolumeTier(i, 'unit_rate', e.target.value ? parseFloat(e.target.value) : '')}
                                                        placeholder="e.g. 0.0900"
                                                    />
                                                    <InputError message={(errors as Record<string, string>)[`volume_tiers.${i}.unit_rate`]} />
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive mt-0.5 h-9 w-9"
                                                    onClick={() => removeVolumeTier(i)}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {isEditing && (
                                <>
                                    <Separator />
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="is_active"
                                            checked={data.is_active}
                                            onCheckedChange={(c) => setData('is_active', c as boolean)}
                                        />
                                        <Label htmlFor="is_active">Active</Label>
                                    </div>
                                </>
                            )}

                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/translation/rate-cards">Cancel</Link>
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

import { Head, Link, router } from '@inertiajs/react';
import { Edit, Mail, Phone, Star, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, TranslatorProfile } from '@/types';

type AvailabilityOption = { value: string; label: string; color: string };
type EnumOption = { value: string; label: string };

type Props = {
    translator: TranslatorProfile;
    availabilities: AvailabilityOption[];
    specialisations: EnumOption[];
    catTools: EnumOption[];
    certifications: EnumOption[];
};

const availabilityColors: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    busy: 'bg-yellow-100 text-yellow-700',
    on_leave: 'bg-gray-100 text-gray-600',
};

export default function TranslatorShow({ translator, availabilities, specialisations, catTools, certifications }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Translators', href: '/translation/translators' },
        { title: translator.contact?.name ?? `Translator #${translator.id}`, href: `/translation/translators/${translator.id}` },
    ];

    function handleDelete() {
        if (confirm('Delete this translator profile? This cannot be undone.')) {
            router.delete(`/translation/translators/${translator.id}`);
        }
    }

    const availabilityOption = availabilities.find((a) => a.value === translator.availability);
    const translatorSpecialisations = specialisations.filter((s) =>
        (translator.specialisations as string[]).includes(s.value),
    );
    const translatorCatTools = catTools.filter((t) => (translator.cat_tools as string[]).includes(t.value));
    const translatorCertifications = certifications.filter((c) => (translator.certifications as string[]).includes(c.value));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={translator.contact?.name ?? 'Translator'} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{translator.contact?.name ?? '—'}</h1>
                        <div className="mt-2 flex items-center gap-3">
                            {availabilityOption && (
                                <Badge className={availabilityColors[translator.availability] ?? ''}>
                                    {availabilityOption.label}
                                </Badge>
                            )}
                            {translator.quality_rating && (
                                <div className="flex items-center gap-1">
                                    {Array.from({ length: translator.quality_rating }).map((_, i) => (
                                        <Star key={i} className="size-4 fill-amber-400 text-amber-400" />
                                    ))}
                                    <span className="text-muted-foreground text-sm">{translator.quality_rating}/5</span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/translation/translators/${translator.id}/edit`}>
                                <Edit className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Contact Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Contact Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {translator.contact?.email && (
                                <div className="flex items-center gap-2">
                                    <Mail className="text-muted-foreground size-4 shrink-0" />
                                    <a href={`mailto:${translator.contact.email}`} className="hover:underline">
                                        {translator.contact.email}
                                    </a>
                                </div>
                            )}
                            {translator.contact?.phone && (
                                <div className="flex items-center gap-2">
                                    <Phone className="text-muted-foreground size-4 shrink-0" />
                                    <span>{translator.contact.phone}</span>
                                </div>
                            )}
                            {!translator.contact?.email && !translator.contact?.phone && (
                                <p className="text-muted-foreground">No contact details.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Language Pairs */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Language Pairs</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {translator.language_pairs?.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {translator.language_pairs.map((lp) => (
                                        <Badge key={lp.id} variant="secondary">
                                            {lp.source_language?.code ?? '?'} → {lp.target_language?.code ?? '?'}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No language pairs assigned.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Service Types */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Services</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {translator.service_types?.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {translator.service_types.map((st) => (
                                        <Badge key={st.id} variant="secondary">
                                            {st.name}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No services assigned.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Specialisations */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Specialisations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {translatorSpecialisations.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {translatorSpecialisations.map((s) => (
                                        <Badge key={s.value} variant="outline">
                                            {s.label}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No specialisations listed.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* CAT Tools */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">CAT Tools</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {translatorCatTools.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {translatorCatTools.map((t) => (
                                        <Badge key={t.value} variant="outline">
                                            {t.label}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No CAT tools listed.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Certifications */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Certifications</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {translatorCertifications.length ? (
                                <div className="flex flex-wrap gap-2">
                                    {translatorCertifications.map((c) => (
                                        <Badge key={c.value} className="bg-blue-100 text-blue-700">
                                            {c.label}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No certifications listed.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Quality Notes */}
                {translator.quality_notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Quality Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm whitespace-pre-line">{translator.quality_notes}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

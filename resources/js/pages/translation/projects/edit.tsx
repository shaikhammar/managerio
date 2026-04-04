import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/Translation/ProjectController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Language, LanguagePair, Project, ProjectAssignmentRole, ServiceType } from '@/types';

type ContactOption = { id: number; name: string };
type Option = { value: string; label: string };
type LanguagePairOption = LanguagePair & { source_language?: Language; target_language?: Language };
type ServiceTypeOption = Pick<ServiceType, 'id' | 'name' | 'default_unit'>;

type AssignmentInput = {
    id?: number;
    contact_id: number | '';
    role: ProjectAssignmentRole | '';
    rate: number | '';
};

type TargetInput = {
    id?: number;
    language_pair_id: number | '';
    service_type_id: number | '';
    word_count: number | '';
    unit_price: number | '';
    assignments: AssignmentInput[];
};

type Props = {
    project: Project;
    customers: ContactOption[];
    suppliers: ContactOption[];
    languages: Language[];
    languagePairs: LanguagePairOption[];
    serviceTypes: ServiceTypeOption[];
    roles: Option[];
};

type SuggestionResult = {
    contact_id: number;
    name: string;
    availability: string;
    quality_rating: number | null;
    score: number;
};

function emptyAssignment(): AssignmentInput {
    return { contact_id: '', role: '', rate: '' };
}

function emptyTarget(): TargetInput {
    return { language_pair_id: '', service_type_id: '', word_count: '', unit_price: '', assignments: [] };
}

export default function ProjectEdit({ project, customers, suppliers, languages, languagePairs, serviceTypes, roles }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/translation/projects' },
        { title: project.reference ?? project.name, href: `/translation/projects/${project.id}` },
        { title: 'Edit', href: '#' },
    ];

    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        contact_id: number | '';
        source_language_id: number | '';
        service_type_id: number | '';
        deadline: string;
        notes: string;
        targets: TargetInput[];
    }>({
        name: project.name,
        contact_id: project.contact_id,
        source_language_id: project.source_language_id,
        service_type_id: project.service_type_id,
        deadline: project.deadline ?? '',
        notes: project.notes ?? '',
        targets:
            project.targets?.map((t) => ({
                id: t.id,
                language_pair_id: t.language_pair_id,
                service_type_id: t.service_type_id ?? '',
                word_count: t.word_count ?? '',
                unit_price: t.unit_price ? parseFloat(t.unit_price) : '',
                assignments:
                    t.assignments?.map((a) => ({
                        id: a.id,
                        contact_id: a.contact_id,
                        role: a.role as ProjectAssignmentRole,
                        rate: a.rate ? parseFloat(a.rate) : '',
                    })) ?? [],
            })) ?? [],
    });

    const [suggestions, setSuggestions] = useState<Record<number, SuggestionResult[]>>({});
    const [suggestionsLoading, setSuggestionsLoading] = useState<Record<number, boolean>>({});

    async function fetchSuggestions(targetIndex: number): Promise<void> {
        const target = data.targets[targetIndex];
        if (!target.language_pair_id) return;

        const serviceTypeId = target.service_type_id || data.service_type_id;
        if (!serviceTypeId) return;

        setSuggestionsLoading((prev) => ({ ...prev, [targetIndex]: true }));
        try {
            const url = `${ProjectController.suggestTranslators.url(project)}?language_pair_id=${target.language_pair_id}&service_type_id=${serviceTypeId}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = (await res.json()) as SuggestionResult[];
            setSuggestions((prev) => ({ ...prev, [targetIndex]: json }));
        } finally {
            setSuggestionsLoading((prev) => ({ ...prev, [targetIndex]: false }));
        }
    }

    function applySuggestion(targetIndex: number, suggestion: SuggestionResult): void {
        const newAssignment: AssignmentInput = {
            contact_id: suggestion.contact_id,
            role: 'translator',
            rate: '',
        };
        setData(
            'targets',
            data.targets.map((t, i) =>
                i === targetIndex ? { ...t, assignments: [...t.assignments, newAssignment] } : t,
            ),
        );
        setSuggestions((prev) => ({ ...prev, [targetIndex]: [] }));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/translation/projects/${project.id}`);
    }

    function addTarget() {
        setData('targets', [...data.targets, emptyTarget()]);
    }

    function removeTarget(index: number) {
        setData(
            'targets',
            data.targets.filter((_, i) => i !== index),
        );
    }

    function updateTarget(index: number, field: keyof TargetInput, value: unknown) {
        const updated = data.targets.map((t, i) => (i === index ? { ...t, [field]: value } : t));
        setData('targets', updated);
    }

    function addAssignment(targetIndex: number) {
        const updated = data.targets.map((t, i) =>
            i === targetIndex ? { ...t, assignments: [...t.assignments, emptyAssignment()] } : t,
        );
        setData('targets', updated);
    }

    function removeAssignment(targetIndex: number, assignmentIndex: number) {
        const updated = data.targets.map((t, i) =>
            i === targetIndex ? { ...t, assignments: t.assignments.filter((_, j) => j !== assignmentIndex) } : t,
        );
        setData('targets', updated);
    }

    function updateAssignment(targetIndex: number, assignmentIndex: number, field: keyof AssignmentInput, value: unknown) {
        const updated = data.targets.map((t, i) =>
            i === targetIndex
                ? {
                      ...t,
                      assignments: t.assignments.map((a, j) => (j === assignmentIndex ? { ...a, [field]: value } : a)),
                  }
                : t,
        );
        setData('targets', updated);
    }

    function pairLabel(pair: LanguagePairOption) {
        const src = pair.source_language;
        const tgt = pair.target_language;

        if (!src || !tgt) {
return `Pair #${pair.id}`;
}

        return `${src.name} (${src.code}) → ${tgt.name} (${tgt.code})`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit — ${project.name}`} />
            <div className="max-w-4xl p-4 md:p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">Edit Project</h1>
                    <p className="text-muted-foreground font-mono text-sm">{project.reference}</p>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Project Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <Label htmlFor="name">Project Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="contact_id">Client</Label>
                                <Select
                                    value={data.contact_id ? String(data.contact_id) : ''}
                                    onValueChange={(v) => setData('contact_id', Number(v))}
                                >
                                    <SelectTrigger id="contact_id">
                                        <SelectValue placeholder="Select client..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.contact_id} />
                            </div>

                            <div>
                                <Label htmlFor="source_language_id">Source Language</Label>
                                <Select
                                    value={data.source_language_id ? String(data.source_language_id) : ''}
                                    onValueChange={(v) => setData('source_language_id', Number(v))}
                                >
                                    <SelectTrigger id="source_language_id">
                                        <SelectValue placeholder="Select source language..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {languages.map((l) => (
                                            <SelectItem key={l.id} value={String(l.id)}>
                                                {l.name} ({l.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.source_language_id} />
                            </div>

                            <div>
                                <Label htmlFor="service_type_id">Default Service Type</Label>
                                <Select
                                    value={data.service_type_id ? String(data.service_type_id) : ''}
                                    onValueChange={(v) => setData('service_type_id', Number(v))}
                                >
                                    <SelectTrigger id="service_type_id">
                                        <SelectValue placeholder="Select service type..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {serviceTypes.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.service_type_id} />
                            </div>

                            <div>
                                <Label htmlFor="deadline">Deadline</Label>
                                <Input id="deadline" type="date" value={data.deadline} onChange={(e) => setData('deadline', e.target.value)} />
                                <InputError message={errors.deadline} />
                            </div>

                            <div className="md:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={3} />
                                <InputError message={errors.notes} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Target Languages</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addTarget}>
                                <Plus className="mr-1 size-4" />
                                Add Target
                            </Button>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {data.targets.length === 0 && (
                                <p className="text-muted-foreground py-4 text-center text-sm">No target languages added yet.</p>
                            )}
                            {data.targets.map((target, ti) => (
                                <div key={ti} className="rounded-lg border p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <h4 className="text-sm font-semibold">Target {ti + 1}</h4>
                                        <Button type="button" variant="ghost" size="sm" onClick={() => removeTarget(ti)}>
                                            <Trash2 className="size-4 text-red-500" />
                                        </Button>
                                    </div>
                                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <Label>Language Pair</Label>
                                            <Select
                                                value={target.language_pair_id ? String(target.language_pair_id) : ''}
                                                onValueChange={(v) => updateTarget(ti, 'language_pair_id', Number(v))}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select language pair..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {languagePairs.map((lp) => (
                                                        <SelectItem key={lp.id} value={String(lp.id)}>
                                                            {pairLabel(lp)}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={(errors as Record<string, string>)[`targets.${ti}.language_pair_id`]} />
                                        </div>
                                        <div>
                                            <Label>Service Type (override)</Label>
                                            <Select
                                                value={target.service_type_id ? String(target.service_type_id) : 'none'}
                                                onValueChange={(v) => updateTarget(ti, 'service_type_id', v === 'none' ? '' : Number(v))}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Use project default..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">Use project default</SelectItem>
                                                    {serviceTypes.map((s) => (
                                                        <SelectItem key={s.id} value={String(s.id)}>
                                                            {s.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div>
                                            <Label>Word Count</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={target.word_count}
                                                onChange={(e) => updateTarget(ti, 'word_count', e.target.value ? Number(e.target.value) : '')}
                                            />
                                        </div>
                                        <div>
                                            <Label>Unit Price (per word)</Label>
                                            <Input
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                value={target.unit_price}
                                                onChange={(e) => updateTarget(ti, 'unit_price', e.target.value ? Number(e.target.value) : '')}
                                                placeholder="Auto from rate card"
                                            />
                                        </div>
                                    </div>

                                    <Separator className="my-3" />
                                    <div className="flex items-center justify-between">
                                        <h5 className="text-sm font-medium">Team Assignments</h5>
                                        <div className="flex gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => void fetchSuggestions(ti)}
                                                disabled={!target.language_pair_id || !!suggestionsLoading[ti]}
                                            >
                                                <Users className="mr-1 size-3" />
                                                {suggestionsLoading[ti] ? 'Loading...' : 'Suggest'}
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => addAssignment(ti)}>
                                                <Plus className="mr-1 size-3" />
                                                Add Person
                                            </Button>
                                        </div>
                                    </div>
                                    {target.assignments.map((assignment, ai) => (
                                        <div key={ai} className="mt-2 grid grid-cols-1 gap-2 md:grid-cols-4">
                                            <div className="md:col-span-2">
                                                <Select
                                                    value={assignment.contact_id ? String(assignment.contact_id) : ''}
                                                    onValueChange={(v) => updateAssignment(ti, ai, 'contact_id', Number(v))}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select person..." />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {suppliers.map((s) => (
                                                            <SelectItem key={s.id} value={String(s.id)}>
                                                                {s.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div>
                                                <Select
                                                    value={assignment.role || ''}
                                                    onValueChange={(v) => updateAssignment(ti, ai, 'role', v)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Role..." />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {roles.map((r) => (
                                                            <SelectItem key={r.value} value={r.value}>
                                                                {r.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="flex gap-2">
                                                <Input
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    value={assignment.rate}
                                                    onChange={(e) => updateAssignment(ti, ai, 'rate', e.target.value ? Number(e.target.value) : '')}
                                                    placeholder="Rate"
                                                />
                                                <Button type="button" variant="ghost" size="icon" onClick={() => removeAssignment(ti, ai)}>
                                                    <Trash2 className="size-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                    {(suggestions[ti] ?? []).length > 0 && (
                                        <div className="mt-3 rounded-md border border-dashed border-blue-200 bg-blue-50 p-3">
                                            <p className="mb-2 text-xs font-medium text-blue-700">Suggested translators — click to add:</p>
                                            <div className="flex flex-wrap gap-2">
                                                {suggestions[ti].map((s) => (
                                                    <button
                                                        key={s.contact_id}
                                                        type="button"
                                                        onClick={() => applySuggestion(ti, s)}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-blue-50"
                                                    >
                                                        {s.name}
                                                        <span
                                                            className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold ${
                                                                s.availability === 'available'
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : s.availability === 'busy'
                                                                      ? 'bg-yellow-100 text-yellow-700'
                                                                      : 'bg-gray-100 text-gray-500'
                                                            }`}
                                                        >
                                                            {s.availability === 'available' ? 'Available' : s.availability === 'busy' ? 'Busy' : 'On Leave'}
                                                        </span>
                                                        {s.quality_rating !== null && (
                                                            <span className="text-yellow-500">{'★'.repeat(s.quality_rating)}</span>
                                                        )}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => history.back()}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

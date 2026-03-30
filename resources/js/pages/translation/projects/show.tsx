import { Head, Link, router, useForm } from '@inertiajs/react';
import { BarChart2, BookOpen, Brain, FileText, FileUp, Trash2, Users, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CatAnalysis, Project, ProjectFileType, StyleGuide, TermBase, TranslationMemory } from '@/types';

type StatusOption = { value: string; label: string; color?: string };
type FileTypeOption = { value: string; label: string };

type Props = {
    project: Project;
    statuses: StatusOption[];
    transitionableStatuses: StatusOption[];
    fileTypes: FileTypeOption[];
    availableTranslationMemories: TranslationMemory[];
    availableTermBases: TermBase[];
    availableStyleGuides: StyleGuide[];
};

const statusColors: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    review: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
    delivered: 'bg-teal-100 text-teal-700',
    invoiced: 'bg-indigo-100 text-indigo-700',
    closed: 'bg-gray-100 text-gray-600',
};

export default function ProjectShow({
    project,
    transitionableStatuses,
    fileTypes,
    availableTranslationMemories,
    availableTermBases,
    availableStyleGuides,
}: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [selectedTmId, setSelectedTmId] = useState('');
    const [selectedTbId, setSelectedTbId] = useState('');
    const [selectedSgId, setSelectedSgId] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/translation/projects' },
        { title: project.reference ?? project.name, href: '#' },
    ];

    const fileForm = useForm<{ file: File | null; type: ProjectFileType }>({
        file: null,
        type: 'source',
    });

    function handleStatusChange(value: string) {
        router.post(`/translation/projects/${project.id}/status`, { status: value });
    }

    function handleGenerateQuote() {
        router.post(`/translation/projects/${project.id}/generate-quote`);
    }

    function handleGenerateInvoice() {
        router.post(`/translation/projects/${project.id}/generate-invoice`);
    }

    function handleGeneratePOs() {
        router.post(`/translation/projects/${project.id}/generate-purchase-orders`);
    }

    function handleFileUpload(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];

        if (!file) {
return;
}

        fileForm.setData('file', file);
        fileForm.post(`/translation/projects/${project.id}/files`, {
            forceFormData: true,
            onSuccess: () => {
                if (fileInputRef.current) {
fileInputRef.current.value = '';
}

                fileForm.reset();
            },
        });
    }

    function handleDeleteFile(fileId: number) {
        if (!confirm('Delete this file?')) {
return;
}

        router.delete(`/translation/projects/${project.id}/files/${fileId}`);
    }

    function handleDeleteProject() {
        if (!confirm('Are you sure you want to delete this project? This cannot be undone.')) {
            return;
        }

        router.delete(`/translation/projects/${project.id}`);
    }

    function handleAttachTm() {
        if (!selectedTmId) {
            return;
        }

        router.post(`/translation/projects/${project.id}/translation-memories/${selectedTmId}`, {}, { onSuccess: () => setSelectedTmId('') });
    }

    function handleDetachTm(tmId: number) {
        router.delete(`/translation/projects/${project.id}/translation-memories/${tmId}`);
    }

    function handleAttachTb() {
        if (!selectedTbId) {
            return;
        }

        router.post(`/translation/projects/${project.id}/term-bases/${selectedTbId}`, {}, { onSuccess: () => setSelectedTbId('') });
    }

    function handleDetachTb(tbId: number) {
        router.delete(`/translation/projects/${project.id}/term-bases/${tbId}`);
    }

    function handleAttachSg() {
        if (!selectedSgId) {
            return;
        }

        router.post(`/translation/projects/${project.id}/style-guides/${selectedSgId}`, {}, { onSuccess: () => setSelectedSgId('') });
    }

    function handleDetachSg(sgId: number) {
        router.delete(`/translation/projects/${project.id}/style-guides/${sgId}`);
    }

    const totalWords = project.targets?.reduce((sum, t) => sum + (t.word_count ?? 0), 0) ?? 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight">{project.name}</h1>
                            <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[project.status] ?? 'bg-muted text-muted-foreground'}`}>
                                {project.status.replace('_', ' ')}
                            </span>
                        </div>
                        <p className="text-muted-foreground mt-1 font-mono text-sm">{project.reference}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {transitionableStatuses.length > 0 && (
                            <Select onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-44">
                                    <SelectValue placeholder="Update status..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {transitionableStatuses.map((s) => (
                                        <SelectItem key={s.value} value={s.value}>
                                            {s.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={`/translation/projects/${project.id}/edit`}>Edit</Link>
                        </Button>
                        <Button variant="outline" className="text-red-600 hover:text-red-600" onClick={handleDeleteProject}>
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Left column — summary + actions */}
                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Project Details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 gap-y-3 text-sm">
                                <span className="text-muted-foreground">Client</span>
                                <span className="font-medium">{project.contact?.name ?? '—'}</span>

                                <span className="text-muted-foreground">Source</span>
                                <span className="font-mono">{project.source_language?.code ?? '—'}</span>

                                <span className="text-muted-foreground">Service</span>
                                <span>{project.service_type?.name ?? '—'}</span>

                                <span className="text-muted-foreground">Deadline</span>
                                <span>{project.deadline ? new Date(project.deadline).toLocaleDateString() : '—'}</span>

                                <span className="text-muted-foreground">Total Words</span>
                                <span className="tabular-nums">{totalWords.toLocaleString()}</span>

                                {project.notes && (
                                    <>
                                        <span className="text-muted-foreground col-span-2 border-t pt-2">Notes</span>
                                        <p className="col-span-2 text-sm">{project.notes}</p>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {project.quote_id ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/sales/quotes/${project.quote_id}`}>View Quote</Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" size="sm" onClick={handleGenerateQuote} disabled={project.status === 'closed'}>
                                        Generate Quote
                                    </Button>
                                )}

                                {project.invoice_id ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/sales/invoices/${project.invoice_id}`}>View Invoice</Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleGenerateInvoice}
                                        disabled={!['completed', 'delivered'].includes(project.status)}
                                    >
                                        Generate Invoice
                                    </Button>
                                )}

                                <Button variant="outline" size="sm" onClick={handleGeneratePOs} disabled={project.status === 'closed'}>
                                    Generate Purchase Orders
                                </Button>

                                <Separator />

                                <Button variant="outline" size="sm" asChild disabled={project.status === 'closed'}>
                                    <Link href={`/translation/projects/${project.id}/cat-analyses/create`}>Add CAT Analysis</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right column — targets + files */}
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Target Languages</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {(!project.targets || project.targets.length === 0) && (
                                    <p className="text-muted-foreground text-sm">No target languages defined.</p>
                                )}
                                {project.targets?.map((target) => {
                                    const src = target.language_pair?.source_language;
                                    const tgt = target.language_pair?.target_language;
                                    const lineTotal =
                                        target.word_count && target.unit_price
                                            ? (target.word_count * parseFloat(target.unit_price)).toFixed(2)
                                            : null;

                                    return (
                                        <div key={target.id} className="rounded-md border p-3">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <p className="font-medium">
                                                        {src && tgt ? `${src.name} (${src.code}) → ${tgt.name} (${tgt.code})` : `Pair #${target.language_pair_id}`}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {target.service_type?.name ?? project.service_type?.name ?? '—'}
                                                        {target.word_count != null && ` · ${target.word_count.toLocaleString()} words`}
                                                        {target.unit_price != null && ` · @${parseFloat(target.unit_price).toFixed(4)}/word`}
                                                        {lineTotal != null && ` = ${lineTotal}`}
                                                    </p>
                                                </div>
                                            </div>

                                            {target.assignments && target.assignments.length > 0 && (
                                                <>
                                                    <Separator className="my-2" />
                                                    <div className="flex flex-col gap-1">
                                                        {target.assignments.map((assignment) => (
                                                            <div key={assignment.id} className="flex items-center justify-between text-sm">
                                                                <div className="flex items-center gap-2">
                                                                    <Users className="text-muted-foreground size-3" />
                                                                    <span>{assignment.contact?.name ?? '—'}</span>
                                                                    <span className="text-muted-foreground text-xs capitalize">{assignment.role}</span>
                                                                </div>
                                                                <div className="flex items-center gap-2">
                                                                    {assignment.rate && (
                                                                        <span className="text-muted-foreground text-xs tabular-nums">
                                                                            @{parseFloat(assignment.rate).toFixed(4)}
                                                                        </span>
                                                                    )}
                                                                    {assignment.purchase_order ? (
                                                                        <Link
                                                                            href={`/purchases/purchase-orders/${assignment.purchase_order.id}`}
                                                                            className="font-mono text-xs hover:underline"
                                                                        >
                                                                            {assignment.purchase_order.number}
                                                                        </Link>
                                                                    ) : (
                                                                        <span className="text-muted-foreground text-xs">No PO</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </>
                                            )}

                                            {target.cat_analyses && target.cat_analyses.length > 0 && (
                                                <>
                                                    <Separator className="my-2" />
                                                    <div className="flex flex-col gap-1">
                                                        {target.cat_analyses.map((analysis: CatAnalysis) => {
                                                            const total = analysis.bands?.reduce((s, b) => s + b.words, 0) ?? 0;
                                                            const weighted = analysis.bands?.reduce(
                                                                (s, b) => s + b.words * ((100 - parseFloat(b.discount_percent)) / 100),
                                                                0,
                                                            ) ?? 0;

                                                            return (
                                                                <div key={analysis.id} className="flex items-center justify-between text-sm">
                                                                    <div className="flex items-center gap-2">
                                                                        <BarChart2 className="text-muted-foreground size-3" />
                                                                        <Link
                                                                            href={`/translation/projects/${project.id}/cat-analyses/${analysis.id}`}
                                                                            className="hover:underline"
                                                                        >
                                                                            {analysis.name}
                                                                        </Link>
                                                                    </div>
                                                                    <span className="text-muted-foreground text-xs tabular-nums">
                                                                        {total.toLocaleString()} → {weighted.toFixed(0)} weighted
                                                                    </span>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-base">Files</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Select
                                        value={fileForm.data.type}
                                        onValueChange={(v) => fileForm.setData('type', v as ProjectFileType)}
                                    >
                                        <SelectTrigger className="h-8 w-32 text-xs">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {fileTypes.map((t) => (
                                                <SelectItem key={t.value} value={t.value}>
                                                    {t.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => fileInputRef.current?.click()}
                                        disabled={fileForm.processing}
                                    >
                                        <FileUp className="mr-1 size-4" />
                                        Upload
                                    </Button>
                                    <input ref={fileInputRef} type="file" className="hidden" onChange={handleFileUpload} />
                                </div>
                            </CardHeader>
                            <CardContent>
                                {(!project.files || project.files.length === 0) && (
                                    <p className="text-muted-foreground text-sm">No files uploaded yet.</p>
                                )}
                                <div className="flex flex-col gap-1">
                                    {project.files?.map((file) => (
                                        <div key={file.id} className="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                                            <div>
                                                <span className="font-medium">{file.name}</span>
                                                <span className="text-muted-foreground ml-2 text-xs capitalize">
                                                    {file.type} · {formatFileSize(file.size)}
                                                </span>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                onClick={() => handleDeleteFile(file.id)}
                                            >
                                                <Trash2 className="size-3.5 text-red-500" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Resources */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Resources</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                {/* Translation Memories */}
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <Brain className="text-muted-foreground size-4" />
                                        <span className="text-sm font-medium">Translation Memories</span>
                                    </div>
                                    <div className="space-y-1">
                                        {(project.translation_memories ?? []).map((tm) => (
                                            <div key={tm.id} className="flex items-center justify-between rounded-md border px-3 py-1.5 text-sm">
                                                <span>{tm.name}</span>
                                                <button type="button" onClick={() => handleDetachTm(tm.id)} className="text-muted-foreground hover:text-foreground">
                                                    <X className="size-3.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    {availableTranslationMemories.length > 0 && (
                                        <div className="mt-2 flex gap-2">
                                            <Select value={selectedTmId} onValueChange={setSelectedTmId}>
                                                <SelectTrigger className="h-8 flex-1 text-xs">
                                                    <SelectValue placeholder="Add TM…" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableTranslationMemories.map((tm) => (
                                                        <SelectItem key={tm.id} value={String(tm.id)}>
                                                            {tm.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Button size="sm" variant="outline" className="h-8 px-3 text-xs" onClick={handleAttachTm} disabled={!selectedTmId}>
                                                Attach
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                <Separator />

                                {/* Term Bases */}
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <BookOpen className="text-muted-foreground size-4" />
                                        <span className="text-sm font-medium">Term Bases</span>
                                    </div>
                                    <div className="space-y-1">
                                        {(project.term_bases ?? []).map((tb) => (
                                            <div key={tb.id} className="flex items-center justify-between rounded-md border px-3 py-1.5 text-sm">
                                                <span>{tb.name}</span>
                                                <button type="button" onClick={() => handleDetachTb(tb.id)} className="text-muted-foreground hover:text-foreground">
                                                    <X className="size-3.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    {availableTermBases.length > 0 && (
                                        <div className="mt-2 flex gap-2">
                                            <Select value={selectedTbId} onValueChange={setSelectedTbId}>
                                                <SelectTrigger className="h-8 flex-1 text-xs">
                                                    <SelectValue placeholder="Add term base…" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableTermBases.map((tb) => (
                                                        <SelectItem key={tb.id} value={String(tb.id)}>
                                                            {tb.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Button size="sm" variant="outline" className="h-8 px-3 text-xs" onClick={handleAttachTb} disabled={!selectedTbId}>
                                                Attach
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                <Separator />

                                {/* Style Guides */}
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <FileText className="text-muted-foreground size-4" />
                                        <span className="text-sm font-medium">Style Guides</span>
                                    </div>
                                    <div className="space-y-1">
                                        {(project.style_guides ?? []).map((sg) => (
                                            <div key={sg.id} className="flex items-center justify-between rounded-md border px-3 py-1.5 text-sm">
                                                <span>{sg.name}</span>
                                                <button type="button" onClick={() => handleDetachSg(sg.id)} className="text-muted-foreground hover:text-foreground">
                                                    <X className="size-3.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    {availableStyleGuides.length > 0 && (
                                        <div className="mt-2 flex gap-2">
                                            <Select value={selectedSgId} onValueChange={setSelectedSgId}>
                                                <SelectTrigger className="h-8 flex-1 text-xs">
                                                    <SelectValue placeholder="Add style guide…" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableStyleGuides.map((sg) => (
                                                        <SelectItem key={sg.id} value={String(sg.id)}>
                                                            {sg.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Button size="sm" variant="outline" className="h-8 px-3 text-xs" onClick={handleAttachSg} disabled={!selectedSgId}>
                                                Attach
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function formatFileSize(bytes: number): string {
    if (bytes >= 1048576) {
return `${(bytes / 1048576).toFixed(1)} MB`;
}

    if (bytes >= 1024) {
return `${(bytes / 1024).toFixed(1)} KB`;
}

    return `${bytes} B`;
}

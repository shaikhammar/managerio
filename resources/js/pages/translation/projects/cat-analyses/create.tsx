import { Head, useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CatMatchBand, Project, ProjectTarget } from '@/types';

type BandOption = { value: CatMatchBand; label: string; defaultDiscount: number };
type ToolOption = { value: string; label: string };

type Props = {
    project: Project;
    targets: ProjectTarget[];
    bands: BandOption[];
    tools: ToolOption[];
};

type BandFormData = {
    band: CatMatchBand;
    words: number;
    discount_percent: number;
};

type ManualFormData = {
    project_target_id: string;
    name: string;
    tool: string;
    bands: BandFormData[];
};

type ImportFormData = {
    project_target_id: string;
    tool: string;
    file: File | null;
};

export default function CatAnalysisCreate({ project, targets, bands, tools }: Props) {
    const [mode, setMode] = useState<'manual' | 'import'>('manual');
    const fileInputRef = useRef<HTMLInputElement>(null);

    const manualImportTools = tools.filter((t) => t.value !== 'manual');

    const manualForm = useForm<ManualFormData>({
        project_target_id: targets[0]?.id?.toString() ?? '',
        name: 'Analysis',
        tool: 'manual',
        bands: bands.map((b) => ({ band: b.value, words: 0, discount_percent: b.defaultDiscount })),
    });

    const importForm = useForm<ImportFormData>({
        project_target_id: targets[0]?.id?.toString() ?? '',
        tool: manualImportTools[0]?.value ?? 'trados',
        file: null,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/translation/projects' },
        { title: project.reference ?? project.name, href: `/translation/projects/${project.id}` },
        { title: 'New CAT Analysis', href: '#' },
    ];

    function handleManualSubmit(e: React.FormEvent) {
        e.preventDefault();
        manualForm.post(`/translation/projects/${project.id}/cat-analyses`);
    }

    function handleImportSubmit(e: React.FormEvent) {
        e.preventDefault();
        importForm.post(`/translation/projects/${project.id}/cat-analyses/import`, { forceFormData: true });
    }

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0] ?? null;
        importForm.setData('file', file);
    }

    function updateBandWords(index: number, words: number) {
        const updated = [...manualForm.data.bands];
        updated[index] = { ...updated[index], words };
        manualForm.setData('bands', updated);
    }

    function updateBandDiscount(index: number, discount: number) {
        const updated = [...manualForm.data.bands];
        updated[index] = { ...updated[index], discount_percent: discount };
        manualForm.setData('bands', updated);
    }

    const totalWords = manualForm.data.bands.reduce((s, b) => s + (b.words || 0), 0);
    const weightedWords = manualForm.data.bands.reduce((s, b) => {
        const effective = (b.words || 0) * ((100 - (b.discount_percent || 0)) / 100);

        return s + effective;
    }, 0);

    function targetLabel(target: ProjectTarget): string {
        const src = target.language_pair?.source_language;
        const tgt = target.language_pair?.target_language;

        if (src && tgt) {
            return `${src.name} → ${tgt.name}${target.service_type ? ` (${target.service_type.name})` : ''}`;
        }

        return `Target #${target.id}`;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New CAT Analysis" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">New CAT Analysis</h1>
                    <div className="flex gap-2">
                        <Button variant={mode === 'manual' ? 'default' : 'outline'} size="sm" onClick={() => setMode('manual')}>
                            Manual Entry
                        </Button>
                        <Button variant={mode === 'import' ? 'default' : 'outline'} size="sm" onClick={() => setMode('import')}>
                            Import File
                        </Button>
                    </div>
                </div>

                {mode === 'manual' && (
                    <form onSubmit={handleManualSubmit} className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Analysis Details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-3">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="target">Target Language</Label>
                                    <Select
                                        value={manualForm.data.project_target_id}
                                        onValueChange={(v) => manualForm.setData('project_target_id', v)}
                                    >
                                        <SelectTrigger id="target">
                                            <SelectValue placeholder="Select target..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {targets.map((t) => (
                                                <SelectItem key={t.id} value={String(t.id)}>
                                                    {targetLabel(t)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {manualForm.errors.project_target_id && (
                                        <p className="text-destructive text-xs">{manualForm.errors.project_target_id}</p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="name">Analysis Name</Label>
                                    <Input
                                        id="name"
                                        value={manualForm.data.name}
                                        onChange={(e) => manualForm.setData('name', e.target.value)}
                                        placeholder="e.g. Initial Analysis"
                                    />
                                    {manualForm.errors.name && <p className="text-destructive text-xs">{manualForm.errors.name}</p>}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="tool">Source Tool</Label>
                                    <Select value={manualForm.data.tool} onValueChange={(v) => manualForm.setData('tool', v)}>
                                        <SelectTrigger id="tool">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tools.map((t) => (
                                                <SelectItem key={t.value} value={t.value}>
                                                    {t.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Match Band Breakdown</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="pb-2 text-left font-medium">Band</th>
                                                <th className="pb-2 text-right font-medium">Words</th>
                                                <th className="pb-2 text-right font-medium">Discount %</th>
                                                <th className="pb-2 text-right font-medium">Effective Words</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {bands.map((band, i) => {
                                                const formBand = manualForm.data.bands[i];
                                                const effective = ((formBand?.words || 0) * ((100 - (formBand?.discount_percent || 0)) / 100)).toFixed(2);

                                                return (
                                                    <tr key={band.value} className="border-b last:border-0">
                                                        <td className="py-2">{band.label}</td>
                                                        <td className="py-2 text-right">
                                                            <Input
                                                                type="number"
                                                                min={0}
                                                                className="ml-auto h-7 w-28 text-right"
                                                                value={formBand?.words ?? 0}
                                                                onChange={(e) => updateBandWords(i, parseInt(e.target.value) || 0)}
                                                            />
                                                        </td>
                                                        <td className="py-2 text-right">
                                                            <Input
                                                                type="number"
                                                                min={0}
                                                                max={100}
                                                                step="0.01"
                                                                className="ml-auto h-7 w-24 text-right"
                                                                value={formBand?.discount_percent ?? 0}
                                                                onChange={(e) => updateBandDiscount(i, parseFloat(e.target.value) || 0)}
                                                            />
                                                        </td>
                                                        <td className="text-muted-foreground py-2 text-right tabular-nums">{effective}</td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                        <tfoot>
                                            <tr className="border-t font-medium">
                                                <td className="pt-2">Total</td>
                                                <td className="pt-2 text-right tabular-nums">{totalWords.toLocaleString()}</td>
                                                <td />
                                                <td className="pt-2 text-right tabular-nums">{weightedWords.toFixed(2)}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={manualForm.processing}>
                                Save Analysis
                            </Button>
                        </div>
                    </form>
                )}

                {mode === 'import' && (
                    <form onSubmit={handleImportSubmit} className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Import from CAT Tool</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-3">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="import-target">Target Language</Label>
                                    <Select
                                        value={importForm.data.project_target_id}
                                        onValueChange={(v) => importForm.setData('project_target_id', v)}
                                    >
                                        <SelectTrigger id="import-target">
                                            <SelectValue placeholder="Select target..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {targets.map((t) => (
                                                <SelectItem key={t.id} value={String(t.id)}>
                                                    {targetLabel(t)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {importForm.errors.project_target_id && (
                                        <p className="text-destructive text-xs">{importForm.errors.project_target_id}</p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="import-tool">CAT Tool Format</Label>
                                    <Select value={importForm.data.tool} onValueChange={(v) => importForm.setData('tool', v)}>
                                        <SelectTrigger id="import-tool">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {manualImportTools.map((t) => (
                                                <SelectItem key={t.value} value={t.value}>
                                                    {t.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {importForm.errors.tool && <p className="text-destructive text-xs">{importForm.errors.tool}</p>}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="import-file">Analysis File (CSV)</Label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            ref={fileInputRef}
                                            id="import-file"
                                            type="file"
                                            accept=".csv,.txt"
                                            className="hidden"
                                            onChange={handleFileChange}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="w-full justify-start gap-2"
                                            onClick={() => fileInputRef.current?.click()}
                                        >
                                            <Upload className="size-4" />
                                            {importForm.data.file ? importForm.data.file.name : 'Choose file…'}
                                        </Button>
                                    </div>
                                    {importForm.errors.file && <p className="text-destructive text-xs">{importForm.errors.file}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <Separator />

                        <div className="text-muted-foreground rounded-md border p-4 text-sm">
                            <p className="font-medium">Supported Formats</p>
                            <ul className="mt-2 list-disc space-y-1 pl-4">
                                <li>
                                    <strong>SDL Trados Studio</strong> — Export analysis as CSV from the Analysis window
                                </li>
                                <li>
                                    <strong>memoQ</strong> — Export Statistics report as CSV from the Project Home
                                </li>
                                <li>
                                    <strong>Phrase (Memsource)</strong> — Export Analysis as CSV from the project
                                </li>
                            </ul>
                        </div>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={importForm.processing || !importForm.data.file}>
                                Import Analysis
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}

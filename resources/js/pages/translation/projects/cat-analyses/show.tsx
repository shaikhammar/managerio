import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CatAnalysis, Project } from '@/types';

type Props = {
    project: Project;
    analysis: CatAnalysis;
    totalWords: number;
    weightedWords: string;
};

const toolLabels: Record<string, string> = {
    manual: 'Manual Entry',
    trados: 'SDL Trados',
    memoq: 'memoQ',
    phrase: 'Phrase (Memsource)',
};

const bandLabels: Record<string, string> = {
    context_match: 'Context Match (101%+)',
    exact_match: 'Exact Match (100%)',
    fuzzy_95_99: 'Fuzzy 95–99%',
    fuzzy_85_94: 'Fuzzy 85–94%',
    fuzzy_75_84: 'Fuzzy 75–84%',
    fuzzy_50_74: 'Fuzzy 50–74%',
    no_match: 'No Match (0–49%)',
    repetitions: 'Repetitions',
};

export default function CatAnalysisShow({ project, analysis, totalWords, weightedWords }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Projects', href: '/translation/projects' },
        { title: project.reference ?? project.name, href: `/translation/projects/${project.id}` },
        { title: analysis.name, href: '#' },
    ];

    function handleDelete() {
        if (!confirm('Delete this CAT analysis?')) {
            return;
        }

        router.delete(`/translation/projects/${project.id}/cat-analyses/${analysis.id}`);
    }

    function handleApplyToQuote() {
        router.post(`/translation/projects/${project.id}/cat-analyses/${analysis.id}/apply-quote`);
    }

    function handleApplyToPO() {
        router.post(`/translation/projects/${project.id}/cat-analyses/${analysis.id}/apply-po`);
    }

    const weightedNum = parseFloat(weightedWords);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`CAT Analysis — ${analysis.name}`} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{analysis.name}</h1>
                        <p className="text-muted-foreground text-sm">{toolLabels[analysis.tool] ?? analysis.tool}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" className="text-red-600 hover:text-red-600" onClick={handleDelete}>
                            <Trash2 className="mr-1 size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 gap-y-3 text-sm">
                                <span className="text-muted-foreground">Total Words</span>
                                <span className="tabular-nums font-medium">{totalWords.toLocaleString()}</span>

                                <span className="text-muted-foreground">Weighted Words</span>
                                <span className="tabular-nums font-medium">{weightedNum.toFixed(2)}</span>

                                <span className="text-muted-foreground">Discount Applied</span>
                                <span className="tabular-nums font-medium">
                                    {totalWords > 0 ? ((1 - weightedNum / totalWords) * 100).toFixed(1) : '0.0'}%
                                </span>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {project.quote_id ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/sales/quotes/${project.quote_id}`}>View Existing Quote</Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" size="sm" onClick={handleApplyToQuote}>
                                        Apply to Quote
                                    </Button>
                                )}
                                <Button variant="outline" size="sm" onClick={handleApplyToPO}>
                                    Apply to Purchase Orders
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/translation/projects/${project.id}`}>Back to Project</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Match Band Breakdown</CardTitle>
                            </CardHeader>
                            <CardContent>
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
                                        {analysis.bands?.map((band) => {
                                            const effective = (band.words * ((100 - parseFloat(band.discount_percent)) / 100)).toFixed(2);

                                            return (
                                                <tr key={band.id} className="border-b last:border-0">
                                                    <td className="py-2">{bandLabels[band.band] ?? band.band}</td>
                                                    <td className="py-2 text-right tabular-nums">{band.words.toLocaleString()}</td>
                                                    <td className="text-muted-foreground py-2 text-right tabular-nums">
                                                        {parseFloat(band.discount_percent).toFixed(0)}%
                                                    </td>
                                                    <td className="py-2 text-right tabular-nums">{effective}</td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t font-medium">
                                            <td className="pt-2">Total</td>
                                            <td className="pt-2 text-right tabular-nums">{totalWords.toLocaleString()}</td>
                                            <td />
                                            <td className="pt-2 text-right tabular-nums">{weightedNum.toFixed(2)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

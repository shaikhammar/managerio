import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    DollarSign,
    Globe,
    Wrench,
    Users,
    UserCheck,
    TrendingUp,
    Clock,
    Activity,
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Translation Reports', href: '/translation/reports' },
];

const reports = [
    {
        title: 'Project Profitability',
        description: 'Revenue vs cost per project with margin breakdown',
        href: '/translation/reports/project-profitability',
        icon: DollarSign,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-50 dark:bg-emerald-900/20',
    },
    {
        title: 'Revenue by Language Pair',
        description: 'Revenue breakdown across language combinations',
        href: '/translation/reports/revenue-language-pair',
        icon: Globe,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
    },
    {
        title: 'Revenue by Service Type',
        description: 'Translation, editing, DTP and other services split',
        href: '/translation/reports/revenue-service-type',
        icon: Wrench,
        color: 'text-violet-600 dark:text-violet-400',
        bg: 'bg-violet-50 dark:bg-violet-900/20',
    },
    {
        title: 'Revenue by Client',
        description: 'Top clients ranked by revenue for a period',
        href: '/translation/reports/revenue-client',
        icon: Users,
        color: 'text-orange-600 dark:text-orange-400',
        bg: 'bg-orange-50 dark:bg-orange-900/20',
    },
    {
        title: 'Translator Utilisation',
        description: 'Word volume, earnings and average rate per translator',
        href: '/translation/reports/translator-utilisation',
        icon: UserCheck,
        color: 'text-cyan-600 dark:text-cyan-400',
        bg: 'bg-cyan-50 dark:bg-cyan-900/20',
    },
    {
        title: 'Average Margin',
        description: 'Gross margin across all projects for a period',
        href: '/translation/reports/average-margin',
        icon: TrendingUp,
        color: 'text-pink-600 dark:text-pink-400',
        bg: 'bg-pink-50 dark:bg-pink-900/20',
    },
    {
        title: 'Delivery Performance',
        description: 'On-time delivery rate and overdue project tracking',
        href: '/translation/reports/delivery-performance',
        icon: Clock,
        color: 'text-amber-600 dark:text-amber-400',
        bg: 'bg-amber-50 dark:bg-amber-900/20',
    },
    {
        title: 'Pipeline Report',
        description: 'Active projects with expected invoice value',
        href: '/translation/reports/pipeline',
        icon: Activity,
        color: 'text-teal-600 dark:text-teal-400',
        bg: 'bg-teal-50 dark:bg-teal-900/20',
    },
];

export default function TranslationReportIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Translation Reports" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
                        <Activity className="size-6" />
                        Translation Reports
                    </h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Profitability, utilisation, and delivery analytics for your translation business
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {reports.map((report) => (
                        <Link key={report.href} href={report.href} className="group">
                            <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md group-hover:scale-[1.02]">
                                <CardHeader className="flex flex-row items-center gap-4 pb-2">
                                    <div className={`rounded-xl p-3 ${report.bg}`}>
                                        <report.icon className={`size-6 ${report.color}`} />
                                    </div>
                                    <div>
                                        <CardTitle className="text-base">{report.title}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription>{report.description}</CardDescription>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}

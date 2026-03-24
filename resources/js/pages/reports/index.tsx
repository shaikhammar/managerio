import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    TrendingUp,
    Scale,
    BookOpen,
    FileText,
    Users,
    Truck,
    Droplets,
    PieChart,
    ListOrdered,
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
];

const reports = [
    {
        title: 'Profit & Loss',
        description: 'Revenue and expenses for a period',
        href: '/reports/profit-and-loss',
        icon: TrendingUp,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-50 dark:bg-emerald-900/20',
    },
    {
        title: 'Balance Sheet',
        description: 'Assets, liabilities, and equity at a point in time',
        href: '/reports/balance-sheet',
        icon: Scale,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
    },
    {
        title: 'Trial Balance',
        description: 'All account balances in debit and credit columns',
        href: '/reports/trial-balance',
        icon: BookOpen,
        color: 'text-purple-600 dark:text-purple-400',
        bg: 'bg-purple-50 dark:bg-purple-900/20',
    },
    {
        title: 'General Ledger',
        description: 'All transactions grouped by account',
        href: '/reports/general-ledger',
        icon: FileText,
        color: 'text-orange-600 dark:text-orange-400',
        bg: 'bg-orange-50 dark:bg-orange-900/20',
    },
    {
        title: 'Aged Receivables',
        description: 'Outstanding customer invoices by age',
        href: '/reports/aged-receivables',
        icon: Users,
        color: 'text-cyan-600 dark:text-cyan-400',
        bg: 'bg-cyan-50 dark:bg-cyan-900/20',
    },
    {
        title: 'Aged Payables',
        description: 'Outstanding supplier invoices by age',
        href: '/reports/aged-payables',
        icon: Truck,
        color: 'text-pink-600 dark:text-pink-400',
        bg: 'bg-pink-50 dark:bg-pink-900/20',
    },
    {
        title: 'Account Transactions',
        description: 'All transactions for a single account with running balance',
        href: '/reports/account-transactions',
        icon: ListOrdered,
        color: 'text-teal-600 dark:text-teal-400',
        bg: 'bg-teal-50 dark:bg-teal-900/20',
    },
    {
        title: 'Cash Flow Statement',
        description: 'Operating, investing, and financing cash movements',
        href: '/reports/cash-flow',
        icon: Droplets,
        color: 'text-sky-600 dark:text-sky-400',
        bg: 'bg-sky-50 dark:bg-sky-900/20',
    },
    {
        title: 'Changes in Equity',
        description: 'Movement in equity accounts including net income',
        href: '/reports/equity-statement',
        icon: PieChart,
        color: 'text-violet-600 dark:text-violet-400',
        bg: 'bg-violet-50 dark:bg-violet-900/20',
    },
];

export default function ReportIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
                        <BarChart3 className="size-6" />
                        Financial Reports
                    </h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Key financial statements derived from your accounting data
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

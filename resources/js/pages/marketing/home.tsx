import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Calculator,
    CheckCircle2,
    CreditCard,
    FileText,
    Globe2,
    Landmark,
    Receipt,
    Shield,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import MarketingLayout from '@/layouts/marketing-layout';

type Props = { canRegister: boolean };

export default function Home({ canRegister }: Props) {
    const registerHref = canRegister ? '/register' : '/login';

    return (
        <MarketingLayout>
            <Head title="ManagerIO — Business Management for Translation Agencies">
                <meta
                    name="description"
                    content="The complete business platform for translation agencies and freelance translators. Client invoicing, supplier payments, bookkeeping, and financial reports — all in one place."
                />
            </Head>

            {/* Hero */}
            <section className="relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 py-24 sm:py-36">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(99,102,241,0.12),transparent)]" />
                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <div className="inline-flex items-center gap-2 rounded-full bg-blue-50 dark:bg-blue-900/30 border border-blue-200/60 dark:border-blue-800/60 px-4 py-1.5 text-sm text-blue-700 dark:text-blue-300 mb-8">
                            <Globe2 className="size-3.5" />
                            Built for language service providers
                        </div>

                        <h1 className="text-4xl sm:text-5xl lg:text-7xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                            Business management
                            <br />
                            <span className="bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-600 bg-clip-text text-transparent">
                                for translation
                            </span>
                        </h1>

                        <p className="mt-6 text-lg sm:text-xl text-muted-foreground max-w-2xl mx-auto leading-relaxed">
                            Invoice clients, pay your translators, reconcile your bank, and generate financial reports — the complete back-office platform designed for translation agencies and freelancers.
                        </p>

                        <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                            <Button
                                size="lg"
                                asChild
                                className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-base px-8 h-12"
                            >
                                <Link href={registerHref}>
                                    Start for Free
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                            <Button variant="outline" size="lg" asChild className="text-base px-8 h-12">
                                <Link href="/features">See All Features</Link>
                            </Button>
                        </div>

                        <div className="mt-10 flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm text-muted-foreground">
                            {['No credit card required', 'Free during beta', 'Multi-business support', 'Double-entry bookkeeping'].map(
                                (item) => (
                                    <div key={item} className="flex items-center gap-1.5">
                                        <CheckCircle2 className="size-4 text-emerald-500" />
                                        {item}
                                    </div>
                                ),
                            )}
                        </div>
                    </div>

                    {/* Dashboard mockup */}
                    <div className="mt-16 relative mx-auto max-w-5xl">
                        <div className="rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-2xl shadow-slate-900/10 dark:shadow-slate-900/60 overflow-hidden bg-white dark:bg-slate-900">
                            {/* Mock browser bar */}
                            <div className="flex items-center gap-1.5 px-4 py-3 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                <span className="size-3 rounded-full bg-red-400/80" />
                                <span className="size-3 rounded-full bg-amber-400/80" />
                                <span className="size-3 rounded-full bg-emerald-400/80" />
                                <div className="ml-3 flex-1 max-w-xs h-5 rounded bg-slate-200 dark:bg-slate-700 text-xs text-slate-400 flex items-center px-2">
                                    managerio.app/dashboard
                                </div>
                            </div>
                            {/* Mock dashboard content */}
                            <div className="p-6 bg-slate-50 dark:bg-slate-900/50 min-h-[320px]">
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                    {[
                                        { label: 'Outstanding Invoices', value: '$24,850', color: 'text-blue-600' },
                                        { label: 'Overdue Payments', value: '$3,200', color: 'text-red-500' },
                                        { label: 'This Month Revenue', value: '$18,400', color: 'text-emerald-600' },
                                        { label: 'Translator Payables', value: '$8,750', color: 'text-indigo-600' },
                                    ].map((stat) => (
                                        <div
                                            key={stat.label}
                                            className="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4"
                                        >
                                            <p className="text-xs text-muted-foreground mb-1">{stat.label}</p>
                                            <p className={`text-xl font-bold ${stat.color}`}>{stat.value}</p>
                                        </div>
                                    ))}
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="sm:col-span-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
                                        <p className="text-xs font-medium text-muted-foreground mb-3">Recent Invoices</p>
                                        <div className="space-y-2">
                                            {[
                                                { client: 'Acme Corp', amount: '$4,200', status: 'Paid', color: 'bg-emerald-100 text-emerald-700' },
                                                { client: 'Globex GmbH', amount: '$7,800', status: 'Sent', color: 'bg-blue-100 text-blue-700' },
                                                { client: 'Initech Ltd', amount: '$2,150', status: 'Overdue', color: 'bg-red-100 text-red-700' },
                                            ].map((inv) => (
                                                <div
                                                    key={inv.client}
                                                    className="flex items-center justify-between text-sm"
                                                >
                                                    <span className="text-slate-700 dark:text-slate-300">{inv.client}</span>
                                                    <div className="flex items-center gap-3">
                                                        <span className="font-medium">{inv.amount}</span>
                                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${inv.color}`}>
                                                            {inv.status}
                                                        </span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
                                        <p className="text-xs font-medium text-muted-foreground mb-3">Active Projects</p>
                                        <div className="space-y-2">
                                            {[
                                                { name: 'EN → DE Technical Manual', progress: 72 },
                                                { name: 'FR → EN Legal Docs', progress: 45 },
                                                { name: 'ES → EN Marketing', progress: 91 },
                                            ].map((proj) => (
                                                <div key={proj.name} className="text-xs">
                                                    <div className="flex justify-between mb-1">
                                                        <span className="text-slate-600 dark:text-slate-400 truncate max-w-[140px]">
                                                            {proj.name}
                                                        </span>
                                                        <span className="text-slate-500">{proj.progress}%</span>
                                                    </div>
                                                    <div className="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                                        <div
                                                            className="h-full bg-blue-500 rounded-full"
                                                            style={{ width: `${proj.progress}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/* Subtle glow behind the mockup */}
                        <div className="absolute -inset-4 -z-10 bg-gradient-to-b from-blue-100/40 to-transparent dark:from-blue-900/20 rounded-3xl blur-2xl" />
                    </div>
                </div>
            </section>

            {/* Stats bar */}
            <section className="py-12 border-b bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-8 text-center">
                        {[
                            { value: '150+', label: 'Routes & features' },
                            { value: '9', label: 'Financial reports' },
                            { value: 'Double-entry', label: 'Bookkeeping engine' },
                            { value: 'Free', label: 'During beta' },
                        ].map((stat) => (
                            <div key={stat.label}>
                                <p className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{stat.value}</p>
                                <p className="mt-1 text-sm text-muted-foreground">{stat.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Industry context */}
            <section className="py-16 border-b bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-2">Built for your workflow</p>
                        <h2 className="text-2xl sm:text-3xl font-bold">Everything a translation business needs</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        {[
                            {
                                icon: FileText,
                                title: 'Client Invoicing',
                                desc: 'Issue professional invoices to your clients, track payment status, and send quotes before a project begins. Credit notes for corrections included.',
                                color: 'text-blue-600',
                                bg: 'bg-blue-50 dark:bg-blue-900/20',
                            },
                            {
                                icon: Users,
                                title: 'Freelancer Payments',
                                desc: 'Record purchase invoices from your translators and editors. Process payments and maintain a full history of what you owe to every vendor.',
                                color: 'text-indigo-600',
                                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                            },
                            {
                                icon: BarChart3,
                                title: 'Financial Reporting',
                                desc: 'Profit & Loss, Balance Sheet, Aged Receivables, and General Ledger — know exactly how your business is performing at any moment.',
                                color: 'text-emerald-600',
                                bg: 'bg-emerald-50 dark:bg-emerald-900/20',
                            },
                        ].map((item) => (
                            <div
                                key={item.title}
                                className="flex gap-4 p-6 rounded-xl border bg-white dark:bg-slate-900 hover:shadow-md transition-shadow"
                            >
                                <div className={`rounded-xl p-3 h-fit ${item.bg}`}>
                                    <item.icon className={`size-6 ${item.color}`} />
                                </div>
                                <div>
                                    <h3 className="font-semibold mb-1">{item.title}</h3>
                                    <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Feature Grid */}
            <section className="py-20 bg-slate-50 dark:bg-slate-900/40">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-14">
                        <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-2">Full feature set</p>
                        <h2 className="text-3xl font-bold tracking-tight">All the tools, none of the complexity</h2>
                        <p className="mt-4 text-lg text-muted-foreground max-w-xl mx-auto">
                            Professional-grade accounting and business management in one clean, fast interface.
                        </p>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {[
                            {
                                icon: Receipt,
                                title: 'Sales & Purchase Invoices',
                                desc: 'Full invoice lifecycle — draft, send, and track payment status for both client bills and vendor costs.',
                                color: 'text-blue-600',
                                bg: 'bg-blue-50 dark:bg-blue-900/20',
                            },
                            {
                                icon: Calculator,
                                title: 'Double-Entry Bookkeeping',
                                desc: 'Every transaction automatically posts a journal entry. Your books stay accurate without any manual effort.',
                                color: 'text-indigo-600',
                                bg: 'bg-indigo-50 dark:bg-indigo-900/20',
                            },
                            {
                                icon: Landmark,
                                title: 'Bank Reconciliation',
                                desc: 'Record bank transactions and reconcile your accounts against bank statements to catch discrepancies.',
                                color: 'text-purple-600',
                                bg: 'bg-purple-50 dark:bg-purple-900/20',
                            },
                            {
                                icon: CreditCard,
                                title: 'Payments & Receipts',
                                desc: 'Record customer receipts and supplier payments with partial payment support and automatic allocation.',
                                color: 'text-amber-600',
                                bg: 'bg-amber-50 dark:bg-amber-900/20',
                            },
                            {
                                icon: BookOpen,
                                title: 'Translation Project Management',
                                desc: 'Manage projects with board and calendar views, language pairs, rate cards, CAT analysis, and translator capacity planning.',
                                color: 'text-teal-600',
                                bg: 'bg-teal-50 dark:bg-teal-900/20',
                            },
                            {
                                icon: Shield,
                                title: 'Multi-Business & Roles',
                                desc: 'Manage multiple entities from one login with owner, admin, editor, and viewer role-based permissions.',
                                color: 'text-pink-600',
                                bg: 'bg-pink-50 dark:bg-pink-900/20',
                            },
                        ].map((feature) => (
                            <Card
                                key={feature.title}
                                className="group hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border bg-white dark:bg-slate-800/60"
                            >
                                <CardHeader className="pb-2">
                                    <div className={`rounded-xl p-3 w-fit ${feature.bg}`}>
                                        <feature.icon className={`size-5 ${feature.color}`} />
                                    </div>
                                    <CardTitle className="text-base mt-3">{feature.title}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CardDescription className="text-sm leading-relaxed">{feature.desc}</CardDescription>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    <div className="text-center mt-10">
                        <Button variant="outline" asChild>
                            <Link href="/features">
                                View all features
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            {/* How it works */}
            <section className="py-20 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-14">
                        <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-2">Simple workflow</p>
                        <h2 className="text-3xl font-bold">Up and running in minutes</h2>
                    </div>
                    <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 max-w-5xl mx-auto">
                        {[
                            {
                                step: '01',
                                title: 'Create your business',
                                desc: 'Set up your agency or freelance profile with your business details and chart of accounts.',
                            },
                            {
                                step: '02',
                                title: 'Add your clients',
                                desc: 'Import or create customer profiles for every client you work with.',
                            },
                            {
                                step: '03',
                                title: 'Manage your translators',
                                desc: 'Add your freelance translators and editors as suppliers to track what you owe them.',
                            },
                            {
                                step: '04',
                                title: 'Invoice and report',
                                desc: 'Issue client invoices, record vendor bills, and generate financial reports in real time.',
                            },
                        ].map((item) => (
                            <div key={item.step} className="text-center">
                                <div className="mx-auto mb-4 size-12 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">
                                    {item.step}
                                </div>
                                <h3 className="font-semibold mb-2">{item.title}</h3>
                                <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="py-24 bg-slate-50 dark:bg-slate-900/40">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <div className="rounded-3xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 p-12 sm:p-16 shadow-xl">
                        <h2 className="text-3xl sm:text-4xl font-bold text-white">Ready to take control of your finances?</h2>
                        <p className="mt-4 text-blue-100 text-lg max-w-xl mx-auto">
                            Start managing your translation business like a pro. Free during our beta period.
                        </p>
                        <div className="flex flex-col sm:flex-row justify-center gap-4 mt-8">
                            <Button size="lg" variant="secondary" asChild className="text-base px-8 h-12">
                                <Link href={registerHref}>
                                    Create Free Account
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                asChild
                                className="text-base px-8 h-12 border-white/40 text-white hover:bg-white/10"
                            >
                                <Link href="/docs">Read the Docs</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}

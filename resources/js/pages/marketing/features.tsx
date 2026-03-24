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
    Zap,
    Clock,
    TrendingUp,
    Layers,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';

type Feature = { title: string; desc: string };
type FeatureGroup = { icon: React.ElementType; color: string; bg: string; heading: string; subheading: string; features: Feature[] };

const featureGroups: FeatureGroup[] = [
    {
        icon: FileText,
        color: 'text-blue-600',
        bg: 'bg-blue-100 dark:bg-blue-900/30',
        heading: 'Sales & Invoicing',
        subheading: 'Full client billing workflow from quote to payment.',
        features: [
            { title: 'Sales Invoices', desc: 'Create professional invoices with line items, discounts, and tax codes. Track status from draft to paid.' },
            { title: 'Quotes', desc: 'Send project quotes to clients before work begins. Convert approved quotes to invoices in one click.' },
            { title: 'Credit Notes', desc: 'Issue credit notes to adjust or reverse invoices. Automatically allocated against outstanding balances.' },
            { title: 'Customer Management', desc: 'Maintain a directory of all your clients with contact details, addresses, and full transaction history.' },
            { title: 'Payment Receipts', desc: 'Record payments received from clients with partial payment support and automatic invoice allocation.' },
        ],
    },
    {
        icon: Users,
        color: 'text-indigo-600',
        bg: 'bg-indigo-100 dark:bg-indigo-900/30',
        heading: 'Purchases & Suppliers',
        subheading: 'Manage all your freelancer and vendor costs.',
        features: [
            { title: 'Purchase Invoices', desc: 'Record bills from freelance translators, editors, and other vendors. Track what you owe and when it\'s due.' },
            { title: 'Supplier Management', desc: 'Manage a directory of all your translators and vendors with their contact information and payment history.' },
            { title: 'Supplier Payments', desc: 'Record payments made to suppliers. Allocate payments to specific purchase invoices automatically.' },
        ],
    },
    {
        icon: Landmark,
        color: 'text-purple-600',
        bg: 'bg-purple-100 dark:bg-purple-900/30',
        heading: 'Banking',
        subheading: 'Keep your bank accounts accurate and reconciled.',
        features: [
            { title: 'Bank Accounts', desc: 'Link your bank accounts to your chart of accounts. Track all inflows and outflows in real time.' },
            { title: 'Bank Transactions', desc: 'Record manual transactions or view auto-generated ones from payments. Search and filter by account.' },
            { title: 'Bank Reconciliation', desc: 'Reconcile your accounts against bank statements. Mark transactions as reconciled and close statement periods.' },
        ],
    },
    {
        icon: Calculator,
        color: 'text-emerald-600',
        bg: 'bg-emerald-100 dark:bg-emerald-900/30',
        heading: 'Accounting',
        subheading: 'True double-entry bookkeeping without the complexity.',
        features: [
            { title: 'Chart of Accounts', desc: 'Fully customizable with asset, liability, equity, revenue, and expense account types and subtypes.' },
            { title: 'Journal Entries', desc: 'Post manual journal entries for adjustments, corrections, and accruals. Reverse entries with one click.' },
            { title: 'Tax Codes', desc: 'Configure tax rates for VAT, GST, or other local taxes. Apply them to invoice line items automatically.' },
            { title: 'Automated Journals', desc: 'Every invoice, payment, and bank transaction automatically creates a balanced journal entry.' },
        ],
    },
    {
        icon: BarChart3,
        color: 'text-amber-600',
        bg: 'bg-amber-100 dark:bg-amber-900/30',
        heading: 'Financial Reports',
        subheading: 'Real-time insight into your business performance.',
        features: [
            { title: 'Profit & Loss', desc: 'See revenue, cost of services, and net profit for any date range. Filter by period instantly.' },
            { title: 'Balance Sheet', desc: 'Snapshot of assets, liabilities, and equity at any point in time.' },
            { title: 'Trial Balance', desc: 'Verify that debits equal credits across all accounts. Instantly spot imbalances.' },
            { title: 'General Ledger', desc: 'Transaction-level detail for every account within a date range. Full audit trail.' },
            { title: 'Aged Receivables', desc: 'See which clients owe you money and how long invoices have been outstanding.' },
            { title: 'Aged Payables', desc: 'Track outstanding supplier balances grouped by age to manage cash flow.' },
        ],
    },
    {
        icon: Shield,
        color: 'text-pink-600',
        bg: 'bg-pink-100 dark:bg-pink-900/30',
        heading: 'Platform & Security',
        subheading: 'Built for teams and agencies of all sizes.',
        features: [
            { title: 'Multi-Business', desc: 'Create and switch between multiple business workspaces from a single account. Perfect for agencies.' },
            { title: 'Role-Based Access', desc: 'Assign Owner, Admin, Editor, or Viewer roles to team members per business.' },
            { title: 'Two-Factor Authentication', desc: 'Secure your account with TOTP-based 2FA. Protect your financial data.' },
            { title: 'Number Sequences', desc: 'Auto-generated document numbers with customizable prefixes for invoices, quotes, payments, and more.' },
        ],
    },
];

const comingSoon = [
    { icon: Globe2, title: 'Translation Projects', desc: 'Full project management with source/target languages, word counts, deadlines, and status tracking.' },
    { icon: Layers, title: 'Language Pairs & Rate Cards', desc: 'Per-word and per-hour rates per language pair, service type, and client. Volume tiers and rush rates.' },
    { icon: TrendingUp, title: 'CAT Tool Analysis', desc: 'Import fuzzy match analysis to auto-calculate project costs and client quotes based on translation memory leverage.' },
    { icon: Clock, title: 'Purchase Orders', desc: 'Issue POs to freelancers per project before work begins. Track delivery and link to purchase invoices.' },
    { icon: Users, title: 'Translator Profiles', desc: 'Extended vendor profiles with language pairs, specializations (legal, medical, technical), and performance tracking.' },
    { icon: BarChart3, title: 'Translation Reports', desc: 'Revenue by language pair, project profitability, translator utilization, and pipeline reporting.' },
];

export default function Features() {
    return (
        <MarketingLayout>
            <Head title="Features — ManagerIO">
                <meta name="description" content="Full feature list: client invoicing, freelancer payments, bank reconciliation, double-entry bookkeeping, and financial reports for translation businesses." />
            </Head>

            {/* Hero */}
            <section className="bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 py-20 sm:py-28 text-center">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-3">Full Feature Set</p>
                    <h1 className="text-4xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Everything you need to run your translation business
                    </h1>
                    <p className="mt-5 text-lg text-muted-foreground">
                        From the first quote to the final payment — ManagerIO covers your entire financial workflow.
                    </p>
                </div>
            </section>

            {/* Current Features */}
            <section className="py-20 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-20">
                    {featureGroups.map((group) => (
                        <div key={group.heading} className="grid lg:grid-cols-5 gap-10">
                            <div className="lg:col-span-2">
                                <div className={`rounded-xl p-3 w-fit ${group.bg} mb-4`}>
                                    <group.icon className={`size-6 ${group.color}`} />
                                </div>
                                <h2 className="text-2xl font-bold mb-2">{group.heading}</h2>
                                <p className="text-muted-foreground">{group.subheading}</p>
                            </div>
                            <div className="lg:col-span-3 grid sm:grid-cols-2 gap-4">
                                {group.features.map((f) => (
                                    <div key={f.title} className="flex gap-3">
                                        <CheckCircle2 className="size-5 text-emerald-500 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="font-medium text-sm">{f.title}</p>
                                            <p className="text-sm text-muted-foreground mt-0.5 leading-relaxed">{f.desc}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Coming Soon */}
            <section className="py-20 bg-slate-50 dark:bg-slate-900/40 border-t">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-14">
                        <div className="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200/60 px-4 py-1.5 text-sm text-indigo-700 dark:text-indigo-300 mb-4">
                            <Zap className="size-3.5" />
                            On the Roadmap
                        </div>
                        <h2 className="text-3xl font-bold">Translation-specific features coming soon</h2>
                        <p className="mt-3 text-muted-foreground max-w-xl mx-auto">
                            We're building the features that make ManagerIO truly purpose-built for the language services industry.
                        </p>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {comingSoon.map((item) => (
                            <div key={item.title} className="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-6 opacity-80">
                                <div className="rounded-xl bg-slate-100 dark:bg-slate-800 p-2.5 w-fit mb-3">
                                    <item.icon className="size-5 text-slate-500" />
                                </div>
                                <h3 className="font-semibold mb-1">{item.title}</h3>
                                <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
                                <p className="text-xs text-indigo-600 font-medium mt-3">Coming soon</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="py-20 bg-white dark:bg-slate-950 text-center">
                <div className="mx-auto max-w-2xl px-4">
                    <h2 className="text-3xl font-bold mb-4">Start using ManagerIO today</h2>
                    <p className="text-muted-foreground mb-8">Free during our beta. No credit card required.</p>
                    <Button size="lg" asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-base px-8">
                        <Link href="/register">
                            Create Free Account
                            <ArrowRight className="ml-2 size-4" />
                        </Link>
                    </Button>
                </div>
            </section>
        </MarketingLayout>
    );
}

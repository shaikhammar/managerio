import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Briefcase,
    Building2,
    CheckCircle2,
    CreditCard,
    FileText,
    Globe2,
    History,
    Landmark,
    PiggyBank,
    Receipt,
    Settings,
    TrendingUp,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';

type Step = {
    number: string;
    title: string;
    icon: React.ElementType;
    color: string;
    bg: string;
    content: React.ReactNode;
};

const steps: Step[] = [
    {
        number: '01',
        title: 'Create your account',
        icon: Settings,
        color: 'text-blue-600',
        bg: 'bg-blue-100 dark:bg-blue-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Visit{' '}
                    <Link href="/register" className="text-blue-600 hover:underline font-medium">
                        /register
                    </Link>{' '}
                    and create a free account using your email address. You can also enable two-factor authentication from{' '}
                    <strong>Settings → Security</strong> for extra protection.
                </p>
                <p>After registering, you'll be prompted to create your first business workspace.</p>
            </div>
        ),
    },
    {
        number: '02',
        title: 'Set up your business',
        icon: BookOpen,
        color: 'text-indigo-600',
        bg: 'bg-indigo-100 dark:bg-indigo-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    When you create a business, ManagerIO automatically sets up a default chart of accounts tailored for service
                    businesses. You can customise it under <strong>Accounting → Chart of Accounts</strong>.
                </p>
                <ul className="space-y-1.5 mt-2">
                    {[
                        'Review and rename accounts to match your business terminology',
                        'Add bank accounts under asset accounts — these link to the Banking module',
                        'Create tax codes under Accounting → Tax Codes (e.g. VAT 20%, GST 10%)',
                        'Invite team members with appropriate roles from the business settings',
                    ].map((item) => (
                        <li key={item} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            {item}
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    {
        number: '03',
        title: 'Add your clients (Customers)',
        icon: Users,
        color: 'text-emerald-600',
        bg: 'bg-emerald-100 dark:bg-emerald-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Go to <strong>Sales → Customers</strong> and add your translation clients. Include their email, address, and tax
                    number if needed for invoices.
                </p>
                <p>
                    From a customer's profile you can view their full invoice and payment history and quickly create a new invoice for
                    them.
                </p>
            </div>
        ),
    },
    {
        number: '04',
        title: 'Add your translators (Suppliers)',
        icon: Users,
        color: 'text-purple-600',
        bg: 'bg-purple-100 dark:bg-purple-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Go to <strong>Purchases → Suppliers</strong> to add your freelance translators, editors, and other vendors. Each
                    supplier gets a full profile with payment history.
                </p>
                <p>
                    When a translator sends you an invoice, record it as a <strong>Purchase Invoice</strong> against their supplier
                    profile.
                </p>
            </div>
        ),
    },
    {
        number: '05',
        title: 'Create your first client invoice',
        icon: FileText,
        color: 'text-amber-600',
        bg: 'bg-amber-100 dark:bg-amber-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>Navigate to <strong>Sales → Invoices → New Invoice</strong> and fill in:</p>
                <ul className="space-y-1.5">
                    {[
                        'Customer — select from your client list',
                        'Invoice date and due date',
                        'Line items — description, quantity, unit price, and optional tax code',
                        'Notes or payment terms (shown on the invoice)',
                    ].map((item) => (
                        <li key={item} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            {item}
                        </li>
                    ))}
                </ul>
                <p>
                    Save as a draft to review, or post immediately. Posted invoices generate a double-entry journal entry
                    automatically.
                </p>
                <p>
                    <strong>Tip:</strong> Use <strong>Sales → Quotes</strong> to send a quote before the project starts. Approved quotes
                    can be converted to invoices in one click. Use <strong>Sales → Credit Notes</strong> to correct or reverse a posted
                    invoice.
                </p>
            </div>
        ),
    },
    {
        number: '06',
        title: 'Record a payment from a client',
        icon: Receipt,
        color: 'text-teal-600',
        bg: 'bg-teal-100 dark:bg-teal-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    When a client pays, go to <strong>Payments → Receipts → Receive Payment</strong>. Select the client and the
                    invoice(s) being paid. Partial payments are supported — ManagerIO tracks the outstanding balance automatically.
                </p>
                <p>The receipt creates a bank transaction and journal entry that reconciles the invoice.</p>
            </div>
        ),
    },
    {
        number: '07',
        title: 'Pay your translators',
        icon: CreditCard,
        color: 'text-pink-600',
        bg: 'bg-pink-100 dark:bg-pink-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    When you pay a freelancer, go to <strong>Payments → Supplier Payments → Make Payment</strong>. Select the supplier
                    and the purchase invoice(s) you're settling.
                </p>
                <p>
                    The payment records the bank outflow and marks the purchase invoice as paid, keeping your payables balanced. Use{' '}
                    <strong>Purchases → Debit Notes</strong> to reverse a posted purchase invoice if a credit is owed.
                </p>
            </div>
        ),
    },
    {
        number: '08',
        title: 'Reconcile your bank account',
        icon: Landmark,
        color: 'text-slate-600',
        bg: 'bg-slate-100 dark:bg-slate-800',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Go to <strong>Banking → Reconciliations</strong> to start a reconciliation. Select a bank account, enter the closing
                    balance from your bank statement, and confirm the date range.
                </p>
                <p>
                    ManagerIO shows all unreconciled transactions for the period. Check off each one against your statement until the
                    difference reaches zero, then close the reconciliation.
                </p>
                <p>
                    You can also view individual transactions under <strong>Banking → Transactions</strong> and manually add transactions
                    that aren't linked to a payment.
                </p>
            </div>
        ),
    },
    {
        number: '09',
        title: 'Set up multi-currency & exchange rates',
        icon: TrendingUp,
        color: 'text-cyan-600',
        bg: 'bg-cyan-100 dark:bg-cyan-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    If you invoice clients or pay translators in foreign currencies, ManagerIO handles the conversion automatically. Set
                    your base (home) currency under <strong>Business Settings</strong>, then:
                </p>
                <ul className="space-y-1.5">
                    {[
                        'Go to Accounting → Exchange Rates to record current rates for each foreign currency',
                        'When creating an invoice or payment, select the currency — the exchange rate field pre-fills from your saved rates',
                        'All amounts are shown in both the document currency and your base currency for reporting',
                        'Financial reports always total in your base currency for accurate P&L and Balance Sheet figures',
                    ].map((item) => (
                        <li key={item} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            {item}
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    {
        number: '10',
        title: 'Set up your translation tools',
        icon: Globe2,
        color: 'text-violet-600',
        bg: 'bg-violet-100 dark:bg-violet-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Before creating translation projects, configure the core translation resources under the{' '}
                    <strong>Translation</strong> menu:
                </p>
                <ul className="space-y-1.5">
                    {[
                        'Languages — add all source and target languages you work with',
                        'Language Pairs — define source→target combinations (e.g. EN→DE, FR→EN)',
                        'Service Types — create service categories such as Translation, Editing, Proofreading, DTP',
                        'Rate Cards — set per-word, per-hour, or per-page rates for each language pair and service type',
                        'Translation Memories — store reusable segment matches to improve consistency and reduce costs',
                        'Term Bases — manage approved terminology for each client or subject area',
                        'Style Guides — attach writing and formatting guidelines to specific clients or projects',
                    ].map((item) => (
                        <li key={item} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            {item}
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    {
        number: '11',
        title: 'Manage translation projects',
        icon: Briefcase,
        color: 'text-fuchsia-600',
        bg: 'bg-fuchsia-100 dark:bg-fuchsia-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Go to <strong>Translation → Projects</strong> to create and manage your translation work. Each project tracks its
                    language pairs, assigned translators, deadlines, and financial details.
                </p>
                <ul className="space-y-1.5">
                    {[
                        'Board view — drag projects through workflow stages (e.g. In Progress, Review, Delivered)',
                        'Calendar view — see project deadlines and milestones on a calendar',
                        'Capacity view — check translator workload to avoid over-assigning',
                        'CAT Analysis — import word-count analysis from CAT tools to calculate costs using your rate cards',
                        'Translation Reports — access 9 specialised reports including pipeline, translator performance, and revenue by language pair',
                    ].map((item) => (
                        <li key={item} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            {item}
                        </li>
                    ))}
                </ul>
                <p>
                    Projects link directly to invoices and purchase orders so you always know the profitability of each job.
                </p>
            </div>
        ),
    },
    {
        number: '12',
        title: 'Budgets & Fixed Assets',
        icon: PiggyBank,
        color: 'text-orange-600',
        bg: 'bg-orange-100 dark:bg-orange-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    <strong>Budgets</strong> — Go to <strong>Accounting → Budgets</strong> to set monthly or annual revenue and expense
                    targets for your accounts. Compare actuals vs budget in the Profit & Loss report to track performance against plan.
                </p>
                <p>
                    <strong>Fixed Assets</strong> — Go to <strong>Accounting → Fixed Assets</strong> to record capital equipment such as
                    computers, software licences, and office furniture. ManagerIO tracks the asset value and can post depreciation
                    journal entries on a schedule.
                </p>
            </div>
        ),
    },
    {
        number: '13',
        title: 'View your financial reports',
        icon: BarChart3,
        color: 'text-blue-600',
        bg: 'bg-blue-100 dark:bg-blue-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Under <strong>Reports</strong> you'll find 9 real-time financial reports filterable by date range. Use the Print
                    button to export any report as a PDF.
                </p>
                <ul className="space-y-1.5">
                    {[
                        { title: 'Profit & Loss', desc: 'Revenue vs costs for any date range, with optional budget comparison' },
                        { title: 'Balance Sheet', desc: 'Assets, liabilities, and equity at a point in time' },
                        { title: 'Cash Flow Statement', desc: 'Operating, investing, and financing cash flows' },
                        { title: 'Equity Statement', desc: 'Changes in owner equity over a period' },
                        { title: 'Trial Balance', desc: 'Verify all debits equal all credits' },
                        { title: 'General Ledger', desc: 'Every transaction for every account in a date range' },
                        { title: 'Account Transactions', desc: 'Drill into a single account to see all movements' },
                        { title: 'Aged Receivables', desc: "Which clients owe you money and how long it's been outstanding" },
                        { title: 'Aged Payables', desc: "What you owe to suppliers and when it's due" },
                    ].map((item) => (
                        <li key={item.title} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            <span>
                                <strong>{item.title}</strong> — {item.desc}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        ),
    },
    {
        number: '14',
        title: 'Track changes with the Audit Log',
        icon: History,
        color: 'text-rose-600',
        bg: 'bg-rose-100 dark:bg-rose-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    Go to <strong>Audit Log</strong> (accessible from the main navigation) to see a full history of every change made
                    in your business workspace — who created, updated, or deleted a record and when.
                </p>
                <p>
                    This is especially useful in multi-user setups where you need to track which team member made a particular change, or
                    to investigate discrepancies in your books.
                </p>
            </div>
        ),
    },
    {
        number: '15',
        title: 'Manage multiple businesses',
        icon: Building2,
        color: 'text-indigo-600',
        bg: 'bg-indigo-100 dark:bg-indigo-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    If you operate multiple entities (e.g. a main agency and a subsidiary, or separate brands), you can create
                    additional business workspaces from the business selector screen. Each workspace is completely independent with its
                    own chart of accounts, contacts, and reports.
                </p>
                <p>
                    Switch between businesses using the business switcher in the sidebar. You can invite different team members to each
                    business with different roles (Owner, Admin, Editor, Viewer).
                </p>
                <p>
                    For inter-company transactions, use <strong>Accounting → Intercompany</strong> to record transfers between your
                    entities.
                </p>
            </div>
        ),
    },
];

export default function GettingStarted() {
    return (
        <MarketingLayout>
            <Head title="Getting Started — ManagerIO Docs">
                <meta
                    name="description"
                    content="Learn how to set up ManagerIO for your translation business. From creating your account to running financial reports — step by step."
                />
            </Head>

            <div className="bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 py-16 sm:py-20">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-3">Documentation</p>
                    <h1 className="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                        Getting Started with ManagerIO
                    </h1>
                    <p className="text-lg text-muted-foreground">
                        Everything you need to go from a blank account to a fully running back-office for your translation business.
                    </p>
                </div>
            </div>

            <div className="py-12 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    {/* Quick nav */}
                    <div className="rounded-xl border bg-slate-50 dark:bg-slate-900 p-5 mb-12">
                        <p className="text-sm font-semibold mb-3 text-muted-foreground uppercase tracking-wider">On this page</p>
                        <ol className="space-y-1.5 columns-2">
                            {steps.map((step) => (
                                <li key={step.number} className="text-sm">
                                    <a href={`#step-${step.number}`} className="text-blue-600 hover:underline">
                                        {step.number}. {step.title}
                                    </a>
                                </li>
                            ))}
                        </ol>
                    </div>

                    {/* Steps */}
                    <div className="space-y-12">
                        {steps.map((step) => (
                            <div key={step.number} id={`step-${step.number}`} className="scroll-mt-20">
                                <div className="flex items-start gap-5">
                                    <div className="shrink-0 flex flex-col items-center">
                                        <div className={`rounded-xl p-2.5 ${step.bg}`}>
                                            <step.icon className={`size-5 ${step.color}`} />
                                        </div>
                                        <div className="w-px flex-1 bg-border mt-3 min-h-[2rem]" />
                                    </div>
                                    <div className="pb-8 flex-1">
                                        <div className="flex items-center gap-2 mb-3">
                                            <span className="text-xs font-mono text-muted-foreground">Step {step.number}</span>
                                        </div>
                                        <h2 className="text-xl font-bold mb-4">{step.title}</h2>
                                        {step.content}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Done */}
                    <div className="mt-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-center text-white">
                        <h2 className="text-2xl font-bold mb-2">You're all set!</h2>
                        <p className="text-blue-100 mb-6">
                            Your translation business is now fully connected — invoicing, payments, projects, and accounting working
                            together in real time.
                        </p>
                        <Button size="lg" variant="secondary" asChild>
                            <Link href="/dashboard">
                                Go to Dashboard
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </MarketingLayout>
    );
}

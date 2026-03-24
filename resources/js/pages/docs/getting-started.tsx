import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Calculator,
    CheckCircle2,
    CreditCard,
    FileText,
    Landmark,
    Receipt,
    Settings,
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
                    Visit <Link href="/register" className="text-blue-600 hover:underline font-medium">/register</Link> and create a free account using your email address. You can also enable two-factor authentication from <strong>Settings → Security</strong> for extra protection.
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
                    When you create a business, ManagerIO automatically sets up a default chart of accounts tailored for service businesses. You can customise it under <strong>Accounting → Chart of Accounts</strong>.
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
                    Go to <strong>Sales → Customers</strong> and add your translation clients. Include their email, address, and tax number if needed for invoices.
                </p>
                <p>
                    From a customer's profile you can view their full invoice and payment history and quickly create a new invoice for them.
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
                    Go to <strong>Purchases → Suppliers</strong> to add your freelance translators, editors, and other vendors. Each supplier gets a full profile with payment history.
                </p>
                <p>
                    When a translator sends you an invoice, record it as a <strong>Purchase Invoice</strong> against their supplier profile.
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
                    Save as a draft to review, or post immediately. Posted invoices generate a double-entry journal entry automatically.
                </p>
                <p>
                    <strong>Tip:</strong> Use <strong>Sales → Quotes</strong> to send a quote before the project starts. Approved quotes can be converted to invoices in one click.
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
                    When a client pays, go to <strong>Payments → Receipts → Receive Payment</strong>. Select the client and the invoice(s) being paid. Partial payments are supported — ManagerIO tracks the outstanding balance automatically.
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
                    When you pay a freelancer, go to <strong>Payments → Supplier Payments → Make Payment</strong>. Select the supplier and the purchase invoice(s) you're settling.
                </p>
                <p>
                    The payment records the bank outflow and marks the purchase invoice as paid, keeping your payables balanced.
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
                    Go to <strong>Banking → Reconciliations</strong> to start a reconciliation. Select a bank account, enter the closing balance from your bank statement, and confirm the date range.
                </p>
                <p>
                    ManagerIO shows all unreconciled transactions for the period. Check off each one against your statement until the difference reaches zero, then close the reconciliation.
                </p>
                <p>
                    You can also view individual transactions under <strong>Banking → Transactions</strong> and manually add transactions that aren't linked to a payment.
                </p>
            </div>
        ),
    },
    {
        number: '09',
        title: 'View your financial reports',
        icon: BarChart3,
        color: 'text-blue-600',
        bg: 'bg-blue-100 dark:bg-blue-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>Under <strong>Reports</strong>, you'll find:</p>
                <ul className="space-y-1.5">
                    {[
                        { title: 'Profit & Loss', desc: 'Revenue vs costs for any date range' },
                        { title: 'Balance Sheet', desc: 'Assets, liabilities, and equity at a point in time' },
                        { title: 'Aged Receivables', desc: 'Which clients owe you money and how long it\'s been outstanding' },
                        { title: 'Aged Payables', desc: 'What you owe to suppliers and when it\'s due' },
                        { title: 'General Ledger', desc: 'Every transaction for every account in a date range' },
                        { title: 'Trial Balance', desc: 'Verify all debits equal all credits' },
                    ].map((item) => (
                        <li key={item.title} className="flex gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500 mt-0.5 shrink-0" />
                            <span><strong>{item.title}</strong> — {item.desc}</span>
                        </li>
                    ))}
                </ul>
                <p>All reports are real-time and filterable by date range. Use the Print button to export as PDF.</p>
            </div>
        ),
    },
    {
        number: '10',
        title: 'Manage multiple businesses',
        icon: Calculator,
        color: 'text-indigo-600',
        bg: 'bg-indigo-100 dark:bg-indigo-900/30',
        content: (
            <div className="space-y-3 text-sm text-muted-foreground leading-relaxed">
                <p>
                    If you operate multiple entities (e.g. a main agency and a subsidiary, or separate brands), you can create additional business workspaces from the business selector screen. Each workspace is completely independent.
                </p>
                <p>
                    Switch between businesses using the business switcher in the sidebar. You can invite different team members to each business with different roles.
                </p>
            </div>
        ),
    },
];

export default function GettingStarted() {
    return (
        <MarketingLayout>
            <Head title="Getting Started — ManagerIO Docs">
                <meta name="description" content="Learn how to set up ManagerIO for your translation business. From creating your account to running financial reports — step by step." />
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
                        <ol className="space-y-1.5">
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
                            Your translation business is now fully connected — invoicing, payments, and accounting working together in real time.
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

import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';

const freePlan = [
    'Unlimited invoices & quotes',
    'Unlimited customers & suppliers',
    'Purchase invoices & payments',
    'Bank reconciliation',
    'Double-entry bookkeeping',
    'Full chart of accounts',
    'Journal entries',
    'Tax codes',
    'All financial reports',
    'Up to 3 businesses',
    'Role-based team access',
    'Two-factor authentication',
];

const faq = [
    {
        q: 'Is it really free?',
        a: 'Yes — ManagerIO is completely free during the beta period. All current features are available to every user at no cost.',
    },
    {
        q: 'Will it stay free?',
        a: 'The core accounting features will remain free. When we launch translation-specific features (projects, rate cards, CAT analysis), those will be part of a paid tier. We\'ll give existing users plenty of notice.',
    },
    {
        q: 'How many businesses can I manage?',
        a: 'During beta you can create up to 3 business workspaces from a single account. Each workspace is completely isolated.',
    },
    {
        q: 'Is my data safe?',
        a: 'Your data is stored securely and never shared with third parties. We use encrypted connections (HTTPS) and support two-factor authentication for your account.',
    },
    {
        q: 'Can I add team members?',
        a: 'Yes. You can invite team members to your business with Owner, Admin, Editor, or Viewer roles to control what they can see and do.',
    },
    {
        q: 'What happens to my data after beta?',
        a: 'Your data will never be deleted when beta ends. You\'ll have the option to continue on a free or paid plan.',
    },
];

export default function Pricing() {
    return (
        <MarketingLayout>
            <Head title="Pricing — ManagerIO">
                <meta name="description" content="ManagerIO is free during beta. All features included — invoicing, bookkeeping, banking, and financial reports for translation businesses." />
            </Head>

            {/* Hero */}
            <section className="bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 py-20 sm:py-28 text-center">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200/60 px-4 py-1.5 text-sm text-emerald-700 dark:text-emerald-300 mb-6">
                        <Zap className="size-3.5" />
                        Free during beta
                    </div>
                    <h1 className="text-4xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Simple, honest pricing
                    </h1>
                    <p className="mt-5 text-lg text-muted-foreground">
                        Everything is free while we're in beta. No hidden fees, no credit card required.
                    </p>
                </div>
            </section>

            {/* Plan */}
            <section className="py-16 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-lg px-4 sm:px-6 lg:px-8">
                    <div className="rounded-2xl border-2 border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden">
                        <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-8 text-white text-center">
                            <p className="text-sm font-semibold uppercase tracking-wider opacity-80 mb-1">Beta Plan</p>
                            <div className="flex items-end justify-center gap-1">
                                <span className="text-6xl font-bold">$0</span>
                                <span className="text-xl opacity-80 mb-2">/month</span>
                            </div>
                            <p className="mt-2 text-blue-100 text-sm">All features included · No expiry date</p>
                        </div>
                        <div className="px-8 py-8">
                            <ul className="space-y-3">
                                {freePlan.map((item) => (
                                    <li key={item} className="flex items-center gap-3 text-sm">
                                        <CheckCircle2 className="size-4 text-emerald-500 shrink-0" />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                            <Button asChild size="lg" className="w-full mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                                <Link href="/register">
                                    Get Started Free
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                            <p className="text-xs text-center text-muted-foreground mt-3">No credit card required</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Coming soon paid tier */}
            <section className="py-12 bg-indigo-50 dark:bg-indigo-950/30 border-y border-indigo-100 dark:border-indigo-900">
                <div className="mx-auto max-w-3xl px-4 text-center">
                    <p className="font-semibold mb-1">Translation Pro tier — coming when the translation features launch</p>
                    <p className="text-sm text-muted-foreground">
                        Projects, rate cards, CAT tool analysis, purchase orders, and translation-specific reports will be part of a paid upgrade. Core accounting features will always be free.
                    </p>
                </div>
            </section>

            {/* FAQ */}
            <section className="py-20 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <h2 className="text-2xl font-bold text-center mb-12">Frequently asked questions</h2>
                    <div className="divide-y">
                        {faq.map((item) => (
                            <div key={item.q} className="py-6">
                                <h3 className="font-semibold mb-2">{item.q}</h3>
                                <p className="text-muted-foreground text-sm leading-relaxed">{item.a}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}

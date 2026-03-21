import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Calculator,
    Landmark,
    Receipt,
    Shield,
    Users,
    Zap,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    canRegister: boolean;
};

export default function Home({ canRegister }: Props) {
    const { auth } = usePage<{ auth: { user?: unknown } }>().props;
    const isLoggedIn = !!auth?.user;

    return (
        <>
            <Head title="ManagerIO — Modern Accounting for Service Businesses">
                <meta name="description" content="All-in-one cloud accounting platform. Invoicing, expense tracking, double-entry bookkeeping, and financial reports for freelancers, consultants, and agencies." />
            </Head>

            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950">
                {/* Navigation */}
                <nav className="sticky top-0 z-50 border-b bg-white/80 backdrop-blur-lg dark:bg-slate-900/80">
                    <div className="mx-auto max-w-7xl flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                        <div className="flex items-center gap-2">
                            <div className="size-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                                <Calculator className="size-4 text-white" />
                            </div>
                            <span className="text-xl font-bold bg-gradient-to-r from-blue-700 to-indigo-600 bg-clip-text text-transparent">
                                ManagerIO
                            </span>
                        </div>
                        <div className="hidden md:flex items-center gap-8">
                            <Link href="/features" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">Features</Link>
                            <Link href="/pricing" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">Pricing</Link>
                            <Link href="/about" className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">About</Link>
                        </div>
                        <div className="flex items-center gap-3">
                            {isLoggedIn ? (
                                <Button asChild>
                                    <Link href="/dashboard">Go to Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href="/login">Sign In</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                                            <Link href="/register">Get Started Free</Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <section className="relative overflow-hidden py-20 sm:py-32">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent dark:from-blue-900/20" />
                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                        <div className="inline-flex items-center gap-2 rounded-full bg-blue-50 dark:bg-blue-900/30 px-4 py-1.5 text-sm text-blue-700 dark:text-blue-300 mb-6 border border-blue-200/50 dark:border-blue-800/50">
                            <Zap className="size-3.5" />
                            Built for service businesses
                        </div>
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                            Accounting that
                            <br />
                            <span className="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">works for you</span>
                        </h1>
                        <p className="mt-6 text-lg sm:text-xl text-muted-foreground max-w-2xl mx-auto leading-relaxed">
                            Professional invoicing, double-entry bookkeeping, and financial reports — all in one platform designed for freelancers, consultants, and agencies.
                        </p>
                        <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                            <Button size="lg" asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-base px-8">
                                <Link href={canRegister ? '/register' : '/login'}>
                                    Start Free Trial
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                            <Button variant="outline" size="lg" asChild className="text-base px-8">
                                <Link href="/features">See Features</Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="py-20 bg-white/50 dark:bg-slate-900/50">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Everything you need to manage your finances
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                Professional-grade accounting tools without the complexity
                            </p>
                        </div>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {[
                                { icon: Receipt, title: 'Professional Invoicing', desc: 'Create and send beautiful invoices. Track payments and overdue balances automatically.', color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
                                { icon: Calculator, title: 'Double-Entry Bookkeeping', desc: 'True double-entry accounting with automated journal entries for every transaction.', color: 'text-indigo-600', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
                                { icon: BarChart3, title: 'Financial Reports', desc: 'P&L, Balance Sheet, Trial Balance, and Aged Receivables — generated instantly.', color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
                                { icon: Users, title: 'Customer & Supplier Management', desc: 'Manage contacts, track outstanding balances, and maintain a complete transaction history.', color: 'text-amber-600', bg: 'bg-amber-50 dark:bg-amber-900/20' },
                                { icon: Landmark, title: 'Bank Reconciliation', desc: 'Match transactions, reconcile bank statements, and keep your books accurate.', color: 'text-purple-600', bg: 'bg-purple-50 dark:bg-purple-900/20' },
                                { icon: Shield, title: 'Multi-Business Support', desc: 'Manage multiple businesses from one account with role-based access control.', color: 'text-pink-600', bg: 'bg-pink-50 dark:bg-pink-900/20' },
                            ].map((feature, i) => (
                                <Card key={i} className="group hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border-0 bg-white dark:bg-slate-800/50">
                                    <CardHeader>
                                        <div className={`rounded-xl p-3 w-fit ${feature.bg}`}>
                                            <feature.icon className={`size-6 ${feature.color}`} />
                                        </div>
                                        <CardTitle className="text-lg mt-2">{feature.title}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <CardDescription className="text-sm leading-relaxed">{feature.desc}</CardDescription>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="py-20">
                    <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                        <div className="rounded-3xl bg-gradient-to-r from-blue-600 to-indigo-600 p-12 sm:p-16">
                            <h2 className="text-3xl font-bold text-white">Ready to simplify your accounting?</h2>
                            <p className="mt-4 text-blue-100 text-lg">
                                Join thousands of service businesses managing their finances with confidence.
                            </p>
                            <Button size="lg" variant="secondary" asChild className="mt-8 text-base px-8">
                                <Link href={canRegister ? '/register' : '/login'}>
                                    Get Started — It's Free
                                    <ArrowRight className="ml-2 size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t py-12 bg-white/50 dark:bg-slate-900/50">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div className="flex items-center gap-2">
                                <div className="size-6 rounded bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                                    <Calculator className="size-3 text-white" />
                                </div>
                                <span className="font-semibold text-muted-foreground">ManagerIO</span>
                            </div>
                            <div className="flex items-center gap-6 text-sm text-muted-foreground">
                                <Link href="/features" className="hover:text-foreground transition-colors">Features</Link>
                                <Link href="/pricing" className="hover:text-foreground transition-colors">Pricing</Link>
                                <Link href="/about" className="hover:text-foreground transition-colors">About</Link>
                            </div>
                            <p className="text-sm text-muted-foreground">© {new Date().getFullYear()} ManagerIO</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

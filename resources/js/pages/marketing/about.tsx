import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Globe2, Heart, Target, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';

export default function About() {
    return (
        <MarketingLayout>
            <Head title="About — ManagerIO">
                <meta name="description" content="ManagerIO is a business management platform built specifically for translation agencies and freelance translators. Our mission is to simplify the business side of the language services industry." />
            </Head>

            {/* Hero */}
            <section className="bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 py-20 sm:py-28 text-center">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-3">Our Story</p>
                    <h1 className="text-4xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Built for the translation industry, by people who understand it
                    </h1>
                    <p className="mt-5 text-lg text-muted-foreground leading-relaxed">
                        Translation agencies and freelancers have unique business needs. Generic accounting software forces you to work around features that weren't designed with language services in mind. We built ManagerIO to change that.
                    </p>
                </div>
            </section>

            {/* Mission */}
            <section className="py-20 bg-white dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-2 gap-16 items-center">
                        <div>
                            <h2 className="text-3xl font-bold mb-6">Our mission</h2>
                            <p className="text-muted-foreground leading-relaxed mb-4">
                                The language services industry is worth hundreds of billions of dollars globally, yet most agencies still manage their finances in spreadsheets or with software that was never designed for them.
                            </p>
                            <p className="text-muted-foreground leading-relaxed mb-4">
                                We're building the platform we wish existed when managing a translation business — one that speaks the language of language service providers. That means understanding projects and language pairs, not just invoices and accounts.
                            </p>
                            <p className="text-muted-foreground leading-relaxed">
                                ManagerIO is inspired by Manager.io — a capable open-source accounting platform — re-imagined as a purpose-built solution for the translation and localization industry.
                            </p>
                        </div>
                        <div className="grid sm:grid-cols-2 gap-5">
                            {[
                                { icon: Target, title: 'Purpose-built', desc: 'Every feature is designed with translation workflows in mind, not retrofitted from generic accounting software.', color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-900/20' },
                                { icon: Globe2, title: 'Industry-aware', desc: 'We understand language pairs, CAT tools, fuzzy matches, and word-count pricing — because that\'s your business.', color: 'text-indigo-600', bg: 'bg-indigo-50 dark:bg-indigo-900/20' },
                                { icon: Zap, title: 'Simple by design', desc: 'Powerful enough for a growing agency. Simple enough for a solo freelancer. No accounting degree required.', color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-900/20' },
                                { icon: Heart, title: 'Free to start', desc: 'We believe small translation businesses deserve great tools regardless of budget. Start free, grow with us.', color: 'text-pink-600', bg: 'bg-pink-50 dark:bg-pink-900/20' },
                            ].map((item) => (
                                <div key={item.title} className="rounded-xl border bg-white dark:bg-slate-900 p-5">
                                    <div className={`rounded-lg p-2.5 w-fit ${item.bg} mb-3`}>
                                        <item.icon className={`size-5 ${item.color}`} />
                                    </div>
                                    <h3 className="font-semibold mb-1">{item.title}</h3>
                                    <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* What we're building */}
            <section className="py-20 bg-slate-50 dark:bg-slate-900/40 border-t">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-3xl font-bold mb-5">Where we're headed</h2>
                    <p className="text-muted-foreground leading-relaxed mb-4">
                        Today, ManagerIO provides a solid foundation: client invoicing, supplier payments, bank reconciliation, full double-entry bookkeeping, and financial reports. These are the essentials every translation business needs.
                    </p>
                    <p className="text-muted-foreground leading-relaxed mb-4">
                        Next, we're building the translation-specific layer: projects and job management, language pair rate cards, CAT tool analysis integration, purchase orders for freelancers, and performance reporting by language pair and service type.
                    </p>
                    <p className="text-muted-foreground leading-relaxed mb-8">
                        Our goal is to be the single platform a translation agency needs — from the first client quote to the annual financial audit.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Button asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                            <Link href="/register">
                                Get Started Free
                                <ArrowRight className="ml-2 size-4" />
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/features">See the Roadmap</Link>
                        </Button>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}

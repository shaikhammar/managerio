import { Link, usePage } from '@inertiajs/react';
import { Calculator, Menu, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

type Props = { children: React.ReactNode };

export default function MarketingLayout({ children }: Props) {
    const { auth } = usePage<{ auth: { user?: unknown } }>().props;
    const isLoggedIn = !!auth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    const navLinks = [
        { href: '/features', label: 'Features' },
        { href: '/pricing', label: 'Pricing' },
        { href: '/docs', label: 'Docs' },
        { href: '/about', label: 'About' },
    ];

    return (
        <div className="min-h-screen bg-white dark:bg-slate-950">
            {/* Navigation */}
            <nav className="sticky top-0 z-50 border-b bg-white/90 backdrop-blur-lg dark:bg-slate-950/90">
                <div className="mx-auto max-w-7xl flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                    {/* Logo */}
                    <Link href="/" className="flex items-center gap-2.5">
                        <div className="size-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                            <Calculator className="size-4 text-white" />
                        </div>
                        <span className="text-lg font-bold bg-gradient-to-r from-blue-700 to-indigo-600 bg-clip-text text-transparent">
                            ManagerIO
                        </span>
                    </Link>

                    {/* Desktop Nav */}
                    <div className="hidden md:flex items-center gap-8">
                        {navLinks.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>

                    {/* Desktop CTA */}
                    <div className="hidden md:flex items-center gap-3">
                        {isLoggedIn ? (
                            <Button asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                                <Link href="/dashboard">Go to Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href="/login">Sign In</Link>
                                </Button>
                                <Button asChild className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                                    <Link href="/register">Start Free</Link>
                                </Button>
                            </>
                        )}
                    </div>

                    {/* Mobile menu toggle */}
                    <button
                        className="md:hidden p-2 rounded-md text-muted-foreground hover:text-foreground"
                        onClick={() => setMobileOpen(!mobileOpen)}
                    >
                        {mobileOpen ? <X className="size-5" /> : <Menu className="size-5" />}
                    </button>
                </div>

                {/* Mobile menu */}
                {mobileOpen && (
                    <div className="md:hidden border-t bg-white dark:bg-slate-950 px-4 py-4 space-y-3">
                        {navLinks.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="block text-sm font-medium text-muted-foreground hover:text-foreground py-1.5"
                                onClick={() => setMobileOpen(false)}
                            >
                                {link.label}
                            </Link>
                        ))}
                        <div className="flex gap-3 pt-2 border-t">
                            {isLoggedIn ? (
                                <Button asChild size="sm" className="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600">
                                    <Link href="/dashboard">Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="outline" asChild size="sm" className="flex-1">
                                        <Link href="/login">Sign In</Link>
                                    </Button>
                                    <Button asChild size="sm" className="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600">
                                        <Link href="/register">Start Free</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </nav>

            {/* Page content */}
            {children}

            {/* Footer */}
            <footer className="border-t bg-slate-50 dark:bg-slate-900/50 py-14">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                        <div className="col-span-2 md:col-span-1">
                            <Link href="/" className="flex items-center gap-2 mb-3">
                                <div className="size-7 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                                    <Calculator className="size-3.5 text-white" />
                                </div>
                                <span className="font-bold text-foreground">ManagerIO</span>
                            </Link>
                            <p className="text-sm text-muted-foreground leading-relaxed">
                                Business management built for language service providers and translation agencies.
                            </p>
                        </div>
                        <div>
                            <p className="text-sm font-semibold mb-3">Product</p>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/features" className="hover:text-foreground transition-colors">Features</Link></li>
                                <li><Link href="/pricing" className="hover:text-foreground transition-colors">Pricing</Link></li>
                                <li><Link href="/docs" className="hover:text-foreground transition-colors">Documentation</Link></li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-sm font-semibold mb-3">Company</p>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/about" className="hover:text-foreground transition-colors">About</Link></li>
                            </ul>
                        </div>
                        <div>
                            <p className="text-sm font-semibold mb-3">Get Started</p>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/register" className="hover:text-foreground transition-colors">Create Account</Link></li>
                                <li><Link href="/login" className="hover:text-foreground transition-colors">Sign In</Link></li>
                            </ul>
                        </div>
                    </div>
                    <div className="border-t pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-muted-foreground">
                        <p>© {new Date().getFullYear()} ManagerIO. All rights reserved.</p>
                        <p>Built for the translation industry.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

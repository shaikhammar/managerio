import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, Landmark } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Account } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Accounts', href: '/banking/accounts' },
];

function fmt(n: number) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(n);
}

type BankAccountWithBalance = Account & { balance: number };

type Props = {
    bankAccounts: BankAccountWithBalance[];
};

export default function BankAccountIndex({ bankAccounts }: Props) {
    const totalBalance = bankAccounts.reduce((sum, a) => sum + a.balance, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bank Accounts" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Bank Accounts</h1>
                        <p className="text-muted-foreground text-sm">
                            Total balance: <span className={`font-bold ${totalBalance >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>{fmt(totalBalance)}</span>
                        </p>
                    </div>
                </div>

                {bankAccounts.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <Landmark className="size-12 mb-4 text-muted-foreground/30" />
                        <h3 className="text-lg font-semibold">No bank accounts</h3>
                        <p className="text-sm text-muted-foreground mt-1">
                            Create a bank account in Chart of Accounts to track balances
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href="/accounting/accounts/create">Create Account</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {bankAccounts.map((account) => (
                            <Link key={account.id} href={`/banking/accounts/${account.id}`}>
                                <div className="rounded-xl border p-6 hover:shadow-md transition-all hover:border-primary/50 cursor-pointer group">
                                    <div className="flex items-center gap-3 mb-4">
                                        <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-2">
                                            <Landmark className="size-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <p className="font-medium group-hover:text-primary transition-colors">{account.name}</p>
                                            <p className="text-xs text-muted-foreground font-mono">{account.code}</p>
                                        </div>
                                    </div>
                                    <p className={`text-2xl font-bold ${account.balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                        {fmt(account.balance)}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

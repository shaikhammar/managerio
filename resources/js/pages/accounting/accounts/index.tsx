import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, Filter } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Account, PaginatedData } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Chart of Accounts', href: '/accounting/accounts' },
];

const typeColors: Record<string, string> = {
    asset: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    liability: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    equity: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    revenue: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    expense: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

type Props = {
    accounts: PaginatedData<Account>;
    filters: { type?: string; search?: string };
    accountTypes: { value: string; label: string }[];
};

export default function AccountIndex({ accounts, filters, accountTypes }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get('/accounting/accounts', { search, type: filters.type }, { preserveState: true });
    }

    function handleTypeChange(type: string) {
        router.get('/accounting/accounts', { search: filters.search, type: type === 'all' ? undefined : type }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chart of Accounts" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Chart of Accounts</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage your business accounts structure
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/accounting/accounts/create">
                            <Plus className="mr-2 size-4" />
                            Add Account
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row gap-3">
                    <form onSubmit={handleSearch} className="flex-1">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Search accounts..."
                                className="pl-9"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                    <Select value={filters.type || 'all'} onValueChange={handleTypeChange}>
                        <SelectTrigger className="w-[180px]">
                            <Filter className="mr-2 size-4" />
                            <SelectValue placeholder="All Types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Types</SelectItem>
                            {accountTypes.map((t) => (
                                <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Accounts Table */}
                <div className="rounded-lg border overflow-hidden">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Code</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Name</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Type</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Status</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {accounts.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="py-12 text-center text-muted-foreground">
                                        No accounts found
                                    </td>
                                </tr>
                            ) : (
                                accounts.data.map((account) => (
                                    <tr key={account.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                        <td className="py-3 px-4 font-mono text-sm">{account.code}</td>
                                        <td className="py-3 px-4">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{account.name}</span>
                                                {account.is_system && (
                                                    <Badge variant="secondary" className="text-xs">System</Badge>
                                                )}
                                            </div>
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${typeColors[account.type] || ''}`}>
                                                {account.type}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className={`text-xs ${account.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}>
                                                {account.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            {!account.is_system && (
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/accounting/accounts/${account.id}/edit`}>Edit</Link>
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {accounts.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {accounts.from} to {accounts.to} of {accounts.total} accounts
                        </p>
                        <div className="flex gap-2">
                            {accounts.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={accounts.links.prev}>Previous</Link>
                                </Button>
                            )}
                            {accounts.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={accounts.links.next}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

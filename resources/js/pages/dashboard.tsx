import { Head, usePage } from '@inertiajs/react';
import {
    TrendingUp,
    TrendingDown,
    DollarSign,
    Receipt,
    CreditCard,
    ArrowUpRight,
    ArrowDownRight,
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, BankAccountSummary, DashboardInvoice, DashboardPayment } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

type DashboardProps = {
    bankAccounts: BankAccountSummary[];
    receivables: number;
    payables: number;
    monthlyRevenue: number;
    monthlyExpenses: number;
    recentInvoices: DashboardInvoice[];
    recentPayments: DashboardPayment[];
};

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(amount);
}

function StatusBadge({ status }: { status: string }) {
    const colors: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        partially_paid: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        overdue: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        void: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${colors[status] || colors.draft}`}>
            {status.replace('_', ' ')}
        </span>
    );
}

export default function Dashboard() {
    const {
        bankAccounts = [],
        receivables = 0,
        payables = 0,
        monthlyRevenue = 0,
        monthlyExpenses = 0,
        recentInvoices = [],
        recentPayments = [],
    } = usePage<{ props: DashboardProps }>().props as DashboardProps;

    const netProfit = monthlyRevenue - monthlyExpenses;
    const totalBankBalance = bankAccounts.reduce((acc, b) => acc + b.balance, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* KPI Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Bank Balance
                            </CardTitle>
                            <DollarSign className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalBankBalance)}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {bankAccounts.length} account{bankAccounts.length !== 1 ? 's' : ''}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Accounts Receivable
                            </CardTitle>
                            <ArrowUpRight className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {formatCurrency(receivables)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Owed by customers</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Accounts Payable
                            </CardTitle>
                            <ArrowDownRight className="size-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                                {formatCurrency(payables)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Owed to suppliers</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Net Profit (Month)
                            </CardTitle>
                            {netProfit >= 0 ? (
                                <TrendingUp className="size-4 text-emerald-500" />
                            ) : (
                                <TrendingDown className="size-4 text-red-500" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${netProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                {formatCurrency(netProfit)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Revenue {formatCurrency(monthlyRevenue)} · Expenses {formatCurrency(monthlyExpenses)}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Two column layout */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent Invoices */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Receipt className="size-5" />
                                Recent Invoices
                            </CardTitle>
                            <CardDescription>Latest sales invoices</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentInvoices.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                                    <Receipt className="size-10 mb-2 opacity-30" />
                                    <p className="text-sm">No invoices yet</p>
                                    <p className="text-xs">Create your first invoice to get started</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {recentInvoices.map((invoice) => (
                                        <div key={invoice.id} className="flex items-center justify-between py-2 border-b last:border-0">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-mono text-sm font-medium">{invoice.number}</span>
                                                    <StatusBadge status={invoice.status} />
                                                </div>
                                                <p className="text-xs text-muted-foreground truncate">
                                                    {invoice.contact || 'No contact'} · {invoice.date}
                                                </p>
                                            </div>
                                            <div className="text-right ml-4">
                                                <p className="text-sm font-semibold">{formatCurrency(invoice.total)}</p>
                                                {invoice.balance_due > 0 && (
                                                    <p className="text-xs text-amber-600 dark:text-amber-400">
                                                        Due: {formatCurrency(invoice.balance_due)}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Payments */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="size-5" />
                                Recent Payments
                            </CardTitle>
                            <CardDescription>Latest receipts & payments</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentPayments.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                                    <CreditCard className="size-10 mb-2 opacity-30" />
                                    <p className="text-sm">No payments yet</p>
                                    <p className="text-xs">Record a payment to track cash flow</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {recentPayments.map((payment) => (
                                        <div key={payment.id} className="flex items-center justify-between py-2 border-b last:border-0">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-mono text-sm font-medium">{payment.number}</span>
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                        payment.type === 'receipt'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    }`}>
                                                        {payment.type === 'receipt' ? 'Receipt' : 'Payment'}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-muted-foreground truncate">
                                                    {payment.contact || 'No contact'} · {payment.date}
                                                </p>
                                            </div>
                                            <div className="text-right ml-4">
                                                <p className={`text-sm font-semibold ${
                                                    payment.type === 'receipt'
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-red-600 dark:text-red-400'
                                                }`}>
                                                    {payment.type === 'receipt' ? '+' : '-'}{formatCurrency(payment.amount)}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Bank Accounts */}
                {bankAccounts.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Bank Accounts</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {bankAccounts.map((account) => (
                                    <div key={account.id} className="flex items-center justify-between rounded-lg border p-4">
                                        <div>
                                            <p className="font-medium">{account.name}</p>
                                        </div>
                                        <p className={`text-lg font-bold ${account.balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'}`}>
                                            {formatCurrency(account.balance)}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

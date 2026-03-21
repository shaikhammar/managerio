import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Receipt,
    CreditCard,
    Landmark,
    BarChart3,
    Calculator,
    ShoppingCart,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { BusinessSwitcher } from '@/components/business-switcher';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem, NavGroup, CurrentBusiness } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const navGroups: NavGroup[] = [
    {
        title: 'Sales',
        icon: Receipt,
        items: [
            { title: 'Customers', href: '/sales/customers' },
            { title: 'Quotes', href: '/sales/quotes' },
            { title: 'Invoices', href: '/sales/invoices' },
            { title: 'Credit Notes', href: '/sales/credit-notes' },
        ],
    },
    {
        title: 'Purchases',
        icon: ShoppingCart,
        items: [
            { title: 'Suppliers', href: '/purchases/suppliers' },
            { title: 'Purchase Invoices', href: '/purchases/invoices' },
        ],
    },
    {
        title: 'Payments',
        icon: CreditCard,
        items: [
            { title: 'Receipts', href: '/payments/receipts' },
            { title: 'Supplier Payments', href: '/payments/supplier-payments' },
        ],
    },
    {
        title: 'Banking',
        icon: Landmark,
        items: [
            { title: 'Bank Accounts', href: '/banking/accounts' },
            { title: 'Transactions', href: '/banking/transactions' },
            { title: 'Reconciliations', href: '/banking/reconciliations' },
        ],
    },
    {
        title: 'Accounting',
        icon: Calculator,
        items: [
            { title: 'Chart of Accounts', href: '/accounting/accounts' },
            { title: 'Journal Entries', href: '/accounting/journal-entries' },
            { title: 'Tax Codes', href: '/accounting/tax-codes' },
        ],
    },
    {
        title: 'Reports',
        icon: BarChart3,
        items: [
            { title: 'All Reports', href: '/reports' },
            { title: 'Profit & Loss', href: '/reports/profit-and-loss' },
            { title: 'Balance Sheet', href: '/reports/balance-sheet' },
            { title: 'Trial Balance', href: '/reports/trial-balance' },
            { title: 'Aged Receivables', href: '/reports/aged-receivables' },
        ],
    },
];

export function AppSidebar() {
    const { currentBusiness } = usePage<{ currentBusiness?: CurrentBusiness }>().props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                {currentBusiness && (
                    <>
                        <SidebarSeparator />
                        <BusinessSwitcher currentBusiness={currentBusiness} />
                    </>
                )}
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} groups={navGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

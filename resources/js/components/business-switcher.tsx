import { router } from '@inertiajs/react';
import { Building2, ChevronsUpDown, Plus } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { CurrentBusiness } from '@/types';

interface BusinessSwitcherProps {
    currentBusiness: CurrentBusiness;
}

export function BusinessSwitcher({ currentBusiness }: BusinessSwitcherProps) {
    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="bg-primary text-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg text-xs font-bold">
                                {currentBusiness.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">{currentBusiness.name}</span>
                                <span className="truncate text-xs text-muted-foreground capitalize">
                                    {currentBusiness.role} · {currentBusiness.currency_code}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-[--radix-dropdown-menu-trigger-width] min-w-56"
                        align="start"
                        sideOffset={4}
                    >
                        <DropdownMenuItem onClick={() => router.visit('/business')}>
                            <Building2 className="mr-2 size-4" />
                            Switch Business
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => router.visit('/business/create')}>
                            <Plus className="mr-2 size-4" />
                            Create New Business
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

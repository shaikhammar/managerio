import { Head, Link, router } from '@inertiajs/react';
import { Building2, Plus, Users, Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';
import type { BusinessSummary } from '@/types';

type Props = {
    businesses: BusinessSummary[];
};

export default function BusinessIndex({ businesses }: Props) {
    return (
        <AuthLayout title="Select Business" description="Choose a business to work with">
            <Head title="Select Business" />
            <div className="w-full max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Your Businesses</h1>
                        <p className="text-muted-foreground text-sm mt-1">
                            Select a business or create a new one
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/business/create">
                            <Plus className="mr-2 size-4" />
                            New Business
                        </Link>
                    </Button>
                </div>

                {businesses.length === 0 ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <Building2 className="size-12 mb-4 text-muted-foreground/30" />
                            <h3 className="text-lg font-semibold">No businesses yet</h3>
                            <p className="text-sm text-muted-foreground mt-1 mb-4">
                                Create your first business to get started with accounting.
                            </p>
                            <Button asChild>
                                <Link href="/business/create">
                                    <Plus className="mr-2 size-4" />
                                    Create Business
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3">
                        {businesses.map((business) => (
                            <Card
                                key={business.id}
                                className="cursor-pointer transition-all hover:border-primary/50 hover:shadow-md"
                                onClick={() => router.post(`/business/${business.id}/switch`)}
                            >
                                <CardContent className="flex items-center gap-4 py-4">
                                    <div className="bg-primary text-primary-foreground flex aspect-square size-12 items-center justify-center rounded-xl text-lg font-bold">
                                        {business.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <h3 className="font-semibold truncate">{business.name}</h3>
                                        <div className="flex items-center gap-3 text-sm text-muted-foreground mt-0.5">
                                            <span className="flex items-center gap-1">
                                                <Globe className="size-3" />
                                                {business.currency_code}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Users className="size-3" />
                                                {business.members_count} member{business.members_count !== 1 ? 's' : ''}
                                            </span>
                                            <span className="capitalize text-xs rounded-full bg-muted px-2 py-0.5">
                                                {business.role}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AuthLayout>
    );
}

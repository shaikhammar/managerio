import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import BankAccountController from '@/actions/App/Http/Controllers/Banking/BankAccountController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bank Accounts', href: BankAccountController.index.url() },
    { title: 'Add Bank Account', href: BankAccountController.create.url() },
];

export default function BankAccountCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        bank_name: '',
        account_number: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(BankAccountController.store.url());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Bank Account" />
            <div className="max-w-lg mx-auto p-4 md:p-6">
                <div className="flex items-center gap-4 mb-6">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={BankAccountController.index.url()}><ArrowLeft className="size-4" /></Link>
                    </Button>
                    <h1 className="text-2xl font-bold tracking-tight">Add Bank Account</h1>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Account Details</CardTitle>
                            <CardDescription>This will appear in your Chart of Accounts as a bank-type asset account.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Account Name *</Label>
                                <Input
                                    id="name"
                                    placeholder="e.g. Business Checking, Main Operating Account"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="code">Account Code *</Label>
                                <Input
                                    id="code"
                                    placeholder="e.g. 1010"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value)}
                                />
                                <InputError message={errors.code} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bank Information</CardTitle>
                            <CardDescription>Optional details stored for your reference.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="bank_name">Bank Name</Label>
                                <Input
                                    id="bank_name"
                                    placeholder="e.g. Chase, Bank of America"
                                    value={data.bank_name}
                                    onChange={(e) => setData('bank_name', e.target.value)}
                                />
                                <InputError message={errors.bank_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="account_number">Account Number</Label>
                                <Input
                                    id="account_number"
                                    placeholder="Last 4 digits or full number"
                                    value={data.account_number}
                                    onChange={(e) => setData('account_number', e.target.value)}
                                />
                                <InputError message={errors.account_number} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" type="button" asChild>
                            <Link href={BankAccountController.index.url()}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Bank Account'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Email settings', href: '/settings/email' },
];

type EmailSettings = {
    smtp_host: string | null;
    smtp_port: number | null;
    smtp_username: string | null;
    smtp_encryption: string | null;
    smtp_from_name: string | null;
    smtp_from_email: string | null;
};

export default function EmailSettings({ business }: { business: EmailSettings }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        smtp_host: business.smtp_host ?? '',
        smtp_port: business.smtp_port ?? (587 as number | string),
        smtp_username: business.smtp_username ?? '',
        smtp_password: '',
        smtp_encryption: business.smtp_encryption ?? 'tls',
        smtp_from_name: business.smtp_from_name ?? '',
        smtp_from_email: business.smtp_from_email ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch('/settings/email');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Email settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        title="Email settings"
                        description="Configure SMTP to send invoices and quotes directly from the app."
                    />

                    <Separator />

                    <form onSubmit={handleSubmit} className="space-y-6">

                        {/* SMTP Server */}
                        <div className="space-y-4">
                            <h3 className="text-sm font-medium text-muted-foreground uppercase tracking-wide">SMTP Server</h3>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="smtp_host">Host</Label>
                                    <Input
                                        id="smtp_host"
                                        value={data.smtp_host}
                                        onChange={(e) => setData('smtp_host', e.target.value)}
                                        placeholder="smtp.example.com"
                                    />
                                    <InputError message={errors.smtp_host} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_port">Port</Label>
                                    <Input
                                        id="smtp_port"
                                        type="number"
                                        value={data.smtp_port}
                                        onChange={(e) => setData('smtp_port', e.target.value)}
                                        placeholder="587"
                                    />
                                    <InputError message={errors.smtp_port} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_encryption">Encryption</Label>
                                <Select value={data.smtp_encryption} onValueChange={(v) => setData('smtp_encryption', v)}>
                                    <SelectTrigger id="smtp_encryption" className="w-[200px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="tls">TLS (recommended)</SelectItem>
                                        <SelectItem value="ssl">SSL</SelectItem>
                                        <SelectItem value="starttls">STARTTLS</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.smtp_encryption} />
                            </div>
                        </div>

                        <Separator />

                        {/* Authentication */}
                        <div className="space-y-4">
                            <h3 className="text-sm font-medium text-muted-foreground uppercase tracking-wide">Authentication</h3>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_username">Username</Label>
                                <Input
                                    id="smtp_username"
                                    value={data.smtp_username}
                                    onChange={(e) => setData('smtp_username', e.target.value)}
                                    placeholder="you@example.com"
                                    autoComplete="off"
                                />
                                <InputError message={errors.smtp_username} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_password">Password</Label>
                                <Input
                                    id="smtp_password"
                                    type="password"
                                    value={data.smtp_password}
                                    onChange={(e) => setData('smtp_password', e.target.value)}
                                    placeholder="Leave blank to keep existing password"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.smtp_password} />
                            </div>
                        </div>

                        <Separator />

                        {/* From Address */}
                        <div className="space-y-4">
                            <h3 className="text-sm font-medium text-muted-foreground uppercase tracking-wide">From Address</h3>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_from_name">From Name</Label>
                                    <Input
                                        id="smtp_from_name"
                                        value={data.smtp_from_name}
                                        onChange={(e) => setData('smtp_from_name', e.target.value)}
                                        placeholder="Your Business Name"
                                    />
                                    <InputError message={errors.smtp_from_name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_from_email">From Email</Label>
                                    <Input
                                        id="smtp_from_email"
                                        type="email"
                                        value={data.smtp_from_email}
                                        onChange={(e) => setData('smtp_from_email', e.target.value)}
                                        placeholder="billing@example.com"
                                    />
                                    <InputError message={errors.smtp_from_email} />
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : 'Save Changes'}
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-muted-foreground">Saved.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AuthLayout from '@/layouts/auth-layout';

const currencies = [
    { code: 'USD', name: 'US Dollar' },
    { code: 'EUR', name: 'Euro' },
    { code: 'GBP', name: 'British Pound' },
    { code: 'CAD', name: 'Canadian Dollar' },
    { code: 'AUD', name: 'Australian Dollar' },
    { code: 'NZD', name: 'New Zealand Dollar' },
    { code: 'INR', name: 'Indian Rupee' },
    { code: 'PKR', name: 'Pakistani Rupee' },
    { code: 'AED', name: 'UAE Dirham' },
    { code: 'SAR', name: 'Saudi Riyal' },
    { code: 'JPY', name: 'Japanese Yen' },
    { code: 'CNY', name: 'Chinese Yuan' },
    { code: 'ZAR', name: 'South African Rand' },
];

const countries = [
    { code: 'US', name: 'United States' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'CA', name: 'Canada' },
    { code: 'AU', name: 'Australia' },
    { code: 'NZ', name: 'New Zealand' },
    { code: 'IN', name: 'India' },
    { code: 'PK', name: 'Pakistan' },
    { code: 'AE', name: 'UAE' },
    { code: 'SA', name: 'Saudi Arabia' },
    { code: 'JP', name: 'Japan' },
    { code: 'CN', name: 'China' },
    { code: 'ZA', name: 'South Africa' },
    { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' },
];

export default function BusinessCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        legal_name: '',
        email: '',
        phone: '',
        country: 'US',
        currency_code: 'USD',
        fiscal_year_start: 1,
        address_line_1: '',
        city: '',
        state: '',
        postal_code: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/business');
    }

    return (
        <AuthLayout title="Create Business" description="Set up a new business for your accounting">
            <Head title="Create Business" />
            <div className="w-full max-w-xl mx-auto">
                <Card>
                    <CardHeader>
                        <CardTitle>Create Business</CardTitle>
                        <CardDescription>
                            Set up a new business entity. A default chart of accounts will be created automatically.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Business Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="My Business"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="legal_name">Legal Name</Label>
                                <Input
                                    id="legal_name"
                                    value={data.legal_name}
                                    onChange={(e) => setData('legal_name', e.target.value)}
                                    placeholder="Legal entity name (optional)"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="country">Country *</Label>
                                    <Select value={data.country} onValueChange={(v) => setData('country', v)}>
                                        <SelectTrigger id="country">
                                            <SelectValue placeholder="Select country" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {countries.map((c) => (
                                                <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.country} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="currency_code">Currency *</Label>
                                    <Select value={data.currency_code} onValueChange={(v) => setData('currency_code', v)}>
                                        <SelectTrigger id="currency_code">
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((c) => (
                                                <SelectItem key={c.code} value={c.code}>{c.code} – {c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_code} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="business@example.com"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="+1 (555) 123-4567"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="address_line_1">Address</Label>
                                <Input
                                    id="address_line_1"
                                    value={data.address_line_1}
                                    onChange={(e) => setData('address_line_1', e.target.value)}
                                    placeholder="Street address"
                                />
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="city">City</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="state">State</Label>
                                    <Input
                                        id="state"
                                        value={data.state}
                                        onChange={(e) => setData('state', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="postal_code">Postal Code</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                    />
                                </div>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Business'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthLayout>
    );
}

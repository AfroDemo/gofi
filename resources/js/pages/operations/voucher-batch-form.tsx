import InputError from '@/components/input-error';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatMinutes, formatMoney, formatSpeed } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Layers3 } from 'lucide-react';
import { FormEvent } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
}

interface Profile {
    id: number;
    name: string;
    tenant: string | null;
    branch: string | null;
    location: string | null;
    package: string | null;
    currency: string | null;
    price: number;
    code_prefix: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    expires_in_days: number | null;
    mac_lock_on_first_use: boolean;
    is_active: boolean;
    vouchers_count: number;
    unused_count: number;
}

interface VoucherBatchFormProps {
    viewer: Viewer;
    profile: Profile;
}

export default function VoucherBatchForm({ viewer, profile }: VoucherBatchFormProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Vouchers', href: '/vouchers' },
        { title: `Generate ${profile.name}`, href: '#' },
    ];

    const form = useForm({
        quantity: '20',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('voucher-batches.store', profile.id));
    };

    const quantity = Number(form.data.quantity || 0);
    const nextSequence = profile.vouchers_count + 1;
    const previewStart = `${profile.code_prefix ?? 'GFI'}-${String(profile.id).padStart(3, '0')}-${String(nextSequence).padStart(4, '0')}`;
    const previewEnd =
        quantity > 0
            ? `${profile.code_prefix ?? 'GFI'}-${String(profile.id).padStart(3, '0')}-${String(nextSequence + quantity - 1).padStart(4, '0')}`
            : previewStart;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Generate ${profile.name}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title={`Generate vouchers for ${profile.name}`}
                    description="Batch generation turns the profile into actual voucher stock that can be printed, sold, or handed out by operators."
                    viewer={viewer}
                />

                <div className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Batch generation</CardTitle>
                            <CardDescription>Create a block of new unused vouchers using this profile’s current settings.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="quantity">Quantity</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        min="1"
                                        max="500"
                                        value={form.data.quantity}
                                        onChange={(event) => form.setData('quantity', event.target.value)}
                                        placeholder="20"
                                    />
                                    <InputError message={form.errors.quantity} />
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Next code</p>
                                        <p className="mt-2 font-medium">{previewStart}</p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Last code in batch</p>
                                        <p className="mt-2 font-medium">{previewEnd}</p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={form.processing || !profile.is_active} className="rounded-xl">
                                        Generate batch
                                    </Button>
                                    <Button asChild type="button" variant="outline" className="rounded-xl">
                                        <Link href={route('vouchers.index')}>Back to vouchers</Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Profile snapshot</CardTitle>
                            <CardDescription>Review the commercial setup before generating stock.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-center gap-2">
                                    <Layers3 className="text-primary size-4" />
                                    <p className="font-medium">{profile.name}</p>
                                </div>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    {[profile.tenant, profile.branch, profile.location, profile.package].filter(Boolean).join(' • ') ||
                                        'Unassigned profile'}
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Price</p>
                                    <p className="mt-2 font-medium">{formatMoney(profile.price, profile.currency)}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Prefix</p>
                                    <p className="mt-2 font-medium">{profile.code_prefix ?? 'GFI'}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                    <p className="mt-2 font-medium">{formatMinutes(profile.duration_minutes)}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                    <p className="mt-2 font-medium">{formatDataLimit(profile.data_limit_mb)}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Speed limit</p>
                                    <p className="mt-2 font-medium">{formatSpeed(profile.speed_limit_kbps)}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Expiry window</p>
                                    <p className="mt-2 font-medium">{profile.expires_in_days ? `${profile.expires_in_days} days` : 'Manual'}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Inventory</p>
                                    <p className="mt-2 font-medium">
                                        {profile.unused_count} unused / {profile.vouchers_count} total
                                    </p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">MAC lock on first use</p>
                                    <p className="mt-2 font-medium">{profile.mac_lock_on_first_use ? 'Enabled' : 'Disabled'}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

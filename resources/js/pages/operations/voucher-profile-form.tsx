import InputError from '@/components/input-error';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatMinutes, formatMoney, formatSpeed } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, Ticket } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
}

interface TenantOption {
    id: number;
    name: string;
    currency: string | null;
}

interface BranchOption {
    id: number;
    tenant_id: number;
    name: string;
    location: string | null;
    label: string;
}

interface PackageOption {
    id: number;
    tenant_id: number;
    branch_id: number | null;
    name: string;
    package_type: string;
    price: number;
    currency: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    label: string;
}

interface VoucherProfilePayload {
    id: number;
    tenant_id: number;
    branch_id: number | null;
    access_package_id: number | null;
    name: string;
    code_prefix: string | null;
    price: string;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    expires_in_days: number | null;
    mac_lock_on_first_use: boolean;
    is_active: boolean;
}

interface VoucherProfileFormProps {
    mode: 'create' | 'edit';
    viewer: Viewer;
    tenantOptions: TenantOption[];
    branchOptions: BranchOption[];
    packageOptions: PackageOption[];
    profile: VoucherProfilePayload | null;
}

export default function VoucherProfileForm({ mode, viewer, tenantOptions, branchOptions, packageOptions, profile }: VoucherProfileFormProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Vouchers', href: '/vouchers' },
        { title: mode === 'create' ? 'New Voucher Profile' : 'Edit Voucher Profile', href: '#' },
    ];

    const form = useForm({
        tenant_id: profile?.tenant_id?.toString() ?? tenantOptions[0]?.id?.toString() ?? '',
        branch_id: profile?.branch_id?.toString() ?? '',
        access_package_id: profile?.access_package_id?.toString() ?? '',
        name: profile?.name ?? '',
        code_prefix: profile?.code_prefix ?? '',
        price: profile?.price ?? '',
        duration_minutes: profile?.duration_minutes?.toString() ?? '',
        data_limit_mb: profile?.data_limit_mb?.toString() ?? '',
        speed_limit_kbps: profile?.speed_limit_kbps?.toString() ?? '',
        expires_in_days: profile?.expires_in_days?.toString() ?? '',
        mac_lock_on_first_use: profile?.mac_lock_on_first_use ?? true,
        is_active: profile?.is_active ?? true,
    });

    const selectedTenant = tenantOptions.find((tenant) => tenant.id.toString() === form.data.tenant_id) ?? null;
    const visibleBranches = branchOptions.filter((branch) => branch.tenant_id.toString() === form.data.tenant_id);
    const visiblePackages = packageOptions.filter((item) => item.tenant_id.toString() === form.data.tenant_id);
    const selectedPackage = visiblePackages.find((item) => item.id.toString() === form.data.access_package_id) ?? null;

    useEffect(() => {
        if (form.data.branch_id && !visibleBranches.some((branch) => branch.id.toString() === form.data.branch_id)) {
            form.setData('branch_id', '');
        }

        if (form.data.access_package_id && !visiblePackages.some((item) => item.id.toString() === form.data.access_package_id)) {
            form.setData('access_package_id', '');
        }
    }, [form, visibleBranches, visiblePackages]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const request = form.transform((data) => ({
            ...data,
            tenant_id: data.tenant_id ? Number(data.tenant_id) : null,
            branch_id: data.branch_id ? Number(data.branch_id) : null,
            access_package_id: data.access_package_id ? Number(data.access_package_id) : null,
            code_prefix: data.code_prefix.trim(),
            price: data.price === '' ? null : data.price,
            duration_minutes: data.duration_minutes === '' ? null : Number(data.duration_minutes),
            data_limit_mb: data.data_limit_mb === '' ? null : Number(data.data_limit_mb),
            speed_limit_kbps: data.speed_limit_kbps === '' ? null : Number(data.speed_limit_kbps),
            expires_in_days: data.expires_in_days === '' ? null : Number(data.expires_in_days),
        }));

        if (mode === 'create') {
            request.post(route('voucher-profiles.store'));
            return;
        }

        request.patch(route('voucher-profiles.update', profile?.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'New Voucher Profile' : `Edit ${profile?.name ?? 'Voucher Profile'}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title={mode === 'create' ? 'Create Voucher Profile' : 'Edit Voucher Profile'}
                    description="Voucher profiles turn sellable packages into printable or agent-issued stock. This workflow sets the commercial and operational rules behind those vouchers."
                    viewer={viewer}
                />

                {tenantOptions.length === 0 && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>No tenant scope available</AlertTitle>
                        <AlertDescription>You need an assigned tenant before you can manage voucher profiles in this workspace.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>{mode === 'create' ? 'Voucher profile details' : 'Update voucher profile'}</CardTitle>
                            <CardDescription>
                                Choose the tenant and package first, then decide how voucher batches should be branded and constrained.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="tenant_id">Tenant</Label>
                                        <Select value={form.data.tenant_id} onValueChange={(value) => form.setData('tenant_id', value)}>
                                            <SelectTrigger id="tenant_id">
                                                <SelectValue placeholder="Select tenant" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tenantOptions.map((tenant) => (
                                                    <SelectItem key={tenant.id} value={tenant.id.toString()}>
                                                        {tenant.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.tenant_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="branch_id">Branch</Label>
                                        <Select
                                            value={form.data.branch_id || 'none'}
                                            onValueChange={(value) => form.setData('branch_id', value === 'none' ? '' : value)}
                                        >
                                            <SelectTrigger id="branch_id">
                                                <SelectValue placeholder="All branches or tenant-wide" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">All branches or tenant-wide</SelectItem>
                                                {visibleBranches.map((branch) => (
                                                    <SelectItem key={branch.id} value={branch.id.toString()}>
                                                        {branch.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.branch_id} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="access_package_id">Access package</Label>
                                        <Select
                                            value={form.data.access_package_id}
                                            onValueChange={(value) => form.setData('access_package_id', value)}
                                        >
                                            <SelectTrigger id="access_package_id">
                                                <SelectValue placeholder="Select package" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {visiblePackages.map((item) => (
                                                    <SelectItem key={item.id} value={item.id.toString()}>
                                                        {item.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.access_package_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">Profile name</Label>
                                        <Input
                                            id="name"
                                            value={form.data.name}
                                            onChange={(event) => form.setData('name', event.target.value)}
                                            placeholder="Quick Hour Scratch Card"
                                        />
                                        <InputError message={form.errors.name} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="code_prefix">Code prefix</Label>
                                        <Input
                                            id="code_prefix"
                                            value={form.data.code_prefix}
                                            onChange={(event) => form.setData('code_prefix', event.target.value.toUpperCase())}
                                            placeholder="CFH"
                                            maxLength={12}
                                        />
                                        <InputError message={form.errors.code_prefix} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="price">Voucher price</Label>
                                        <Input
                                            id="price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={form.data.price}
                                            onChange={(event) => form.setData('price', event.target.value)}
                                            placeholder={selectedPackage ? String(selectedPackage.price) : '1000'}
                                        />
                                        <InputError message={form.errors.price} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="duration_minutes">Duration in minutes</Label>
                                        <Input
                                            id="duration_minutes"
                                            type="number"
                                            min="1"
                                            value={form.data.duration_minutes}
                                            onChange={(event) => form.setData('duration_minutes', event.target.value)}
                                            placeholder={selectedPackage?.duration_minutes ? String(selectedPackage.duration_minutes) : '60'}
                                        />
                                        <InputError message={form.errors.duration_minutes} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="data_limit_mb">Data limit in MB</Label>
                                        <Input
                                            id="data_limit_mb"
                                            type="number"
                                            min="1"
                                            value={form.data.data_limit_mb}
                                            onChange={(event) => form.setData('data_limit_mb', event.target.value)}
                                            placeholder={selectedPackage?.data_limit_mb ? String(selectedPackage.data_limit_mb) : '2048'}
                                        />
                                        <InputError message={form.errors.data_limit_mb} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="speed_limit_kbps">Speed limit in Kbps</Label>
                                        <Input
                                            id="speed_limit_kbps"
                                            type="number"
                                            min="1"
                                            value={form.data.speed_limit_kbps}
                                            onChange={(event) => form.setData('speed_limit_kbps', event.target.value)}
                                            placeholder={selectedPackage?.speed_limit_kbps ? String(selectedPackage.speed_limit_kbps) : '4096'}
                                        />
                                        <InputError message={form.errors.speed_limit_kbps} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="expires_in_days">Voucher expires in days</Label>
                                        <Input
                                            id="expires_in_days"
                                            type="number"
                                            min="1"
                                            max="365"
                                            value={form.data.expires_in_days}
                                            onChange={(event) => form.setData('expires_in_days', event.target.value)}
                                            placeholder="30"
                                        />
                                        <InputError message={form.errors.expires_in_days} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="mac_lock_on_first_use"
                                            checked={form.data.mac_lock_on_first_use}
                                            onCheckedChange={(checked) => form.setData('mac_lock_on_first_use', checked === true)}
                                        />
                                        <div>
                                            <Label htmlFor="mac_lock_on_first_use">Lock to first device</Label>
                                            <p className="text-muted-foreground text-sm">Reduces voucher sharing after the first successful login.</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="is_active"
                                            checked={form.data.is_active}
                                            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                        />
                                        <div>
                                            <Label htmlFor="is_active">Profile is active</Label>
                                            <p className="text-muted-foreground text-sm">
                                                Inactive profiles stay in history but are not used for new stock.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={form.processing || tenantOptions.length === 0} className="rounded-xl">
                                        {mode === 'create' ? 'Create profile' : 'Save changes'}
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
                            <CardTitle>Operational guidance</CardTitle>
                            <CardDescription>
                                Profiles should mirror how operators actually print or issue voucher stock in the field.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-center gap-2">
                                    <Ticket className="text-primary size-4" />
                                    <p className="font-medium">Selected package defaults</p>
                                </div>
                                {selectedPackage ? (
                                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Price</p>
                                            <p className="mt-2 font-medium">{formatMoney(selectedPackage.price, selectedPackage.currency)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Type</p>
                                            <p className="mt-2 font-medium capitalize">{selectedPackage.package_type}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                            <p className="mt-2 font-medium">{formatMinutes(selectedPackage.duration_minutes)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                            <p className="mt-2 font-medium">{formatDataLimit(selectedPackage.data_limit_mb)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3 sm:col-span-2">
                                            <p className="text-muted-foreground text-xs uppercase">Speed limit</p>
                                            <p className="mt-2 font-medium">{formatSpeed(selectedPackage.speed_limit_kbps)}</p>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        Choose an access package to see the default commercial settings that this voucher profile can inherit.
                                    </p>
                                )}
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="text-muted-foreground text-xs font-semibold tracking-[0.18em] uppercase">Tenant currency</p>
                                <div className="mt-3 flex items-center gap-2">
                                    <Badge variant="secondary" className="rounded-full px-3 py-1">
                                        {selectedTenant?.currency ?? selectedPackage?.currency ?? 'No currency'}
                                    </Badge>
                                    <p className="text-muted-foreground text-sm">
                                        Voucher price will display using the selected tenant or package currency.
                                    </p>
                                </div>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Practical profile tips</p>
                                <ul className="text-muted-foreground mt-3 space-y-2 text-sm">
                                    <li>Keep code prefixes short so printed vouchers are still easy to read and type.</li>
                                    <li>Use expiry days when you want inventory to age out automatically after printing.</li>
                                    <li>Leave numeric fields blank if the package defaults should be reused without override.</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

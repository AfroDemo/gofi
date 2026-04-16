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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, Package2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface PackageTypeOption {
    value: string;
    label: string;
    description: string;
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

interface PackagePayload {
    id: number;
    tenant_id: number;
    branch_id: number | null;
    name: string;
    package_type: string;
    description: string | null;
    price: string;
    currency: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    is_active: boolean;
}

interface PackageFormPageProps {
    mode: 'create' | 'edit';
    viewer: Viewer;
    packageTypes: PackageTypeOption[];
    tenantOptions: TenantOption[];
    branchOptions: BranchOption[];
    package: PackagePayload | null;
}

export default function PackageForm({ mode, viewer, packageTypes, tenantOptions, branchOptions, package: currentPackage }: PackageFormPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Packages', href: '/packages' },
        { title: mode === 'create' ? 'New Package' : 'Edit Package', href: '#' },
    ];

    const form = useForm({
        tenant_id: currentPackage?.tenant_id?.toString() ?? tenantOptions[0]?.id?.toString() ?? '',
        branch_id: currentPackage?.branch_id?.toString() ?? '',
        name: currentPackage?.name ?? '',
        package_type: currentPackage?.package_type ?? packageTypes[0]?.value ?? 'time',
        description: currentPackage?.description ?? '',
        price: currentPackage?.price ?? '',
        duration_minutes: currentPackage?.duration_minutes?.toString() ?? '',
        data_limit_mb: currentPackage?.data_limit_mb?.toString() ?? '',
        speed_limit_kbps: currentPackage?.speed_limit_kbps?.toString() ?? '',
        is_active: currentPackage?.is_active ?? true,
    });

    const selectedTenant = tenantOptions.find((tenant) => tenant.id.toString() === form.data.tenant_id) ?? null;
    const visibleBranches = branchOptions.filter((branch) => branch.tenant_id.toString() === form.data.tenant_id);
    const selectedType = packageTypes.find((type) => type.value === form.data.package_type) ?? null;

    useEffect(() => {
        if (form.data.branch_id && !visibleBranches.some((branch) => branch.id.toString() === form.data.branch_id)) {
            form.setData('branch_id', '');
        }
    }, [form, visibleBranches]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const request = form.transform((data) => ({
            ...data,
            tenant_id: data.tenant_id ? Number(data.tenant_id) : null,
            branch_id: data.branch_id ? Number(data.branch_id) : null,
            price: data.price === '' ? null : data.price,
            description: data.description.trim() === '' ? null : data.description,
            duration_minutes: data.duration_minutes === '' ? null : Number(data.duration_minutes),
            data_limit_mb: data.data_limit_mb === '' ? null : Number(data.data_limit_mb),
            speed_limit_kbps: data.speed_limit_kbps === '' ? null : Number(data.speed_limit_kbps),
        }));

        if (mode === 'create') {
            request.post(route('packages.store'));
            return;
        }

        request.patch(route('packages.update', currentPackage?.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'New Package' : `Edit ${currentPackage?.name ?? 'Package'}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title={mode === 'create' ? 'Create Access Package' : 'Edit Access Package'}
                    description="This is the first real management flow in Go-Fi. Packages now move beyond seeded data and can be created or adjusted from the actual operator workspace."
                    viewer={viewer}
                />

                {tenantOptions.length === 0 && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>No tenant scope available</AlertTitle>
                        <AlertDescription>You need an assigned tenant before you can create or edit packages in this workspace.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>{mode === 'create' ? 'Package details' : 'Update package details'}</CardTitle>
                            <CardDescription>
                                Set the commercial shape of the offer, then map it to a branch if it should be sold in a specific location.
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
                                        <Label htmlFor="name">Package name</Label>
                                        <Input
                                            id="name"
                                            value={form.data.name}
                                            onChange={(event) => form.setData('name', event.target.value)}
                                            placeholder="Starter hour, Day pass, Flex bundle"
                                        />
                                        <InputError message={form.errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="package_type">Package type</Label>
                                        <Select value={form.data.package_type} onValueChange={(value) => form.setData('package_type', value)}>
                                            <SelectTrigger id="package_type">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {packageTypes.map((type) => (
                                                    <SelectItem key={type.value} value={type.value}>
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.package_type} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <textarea
                                        id="description"
                                        value={form.data.description}
                                        onChange={(event) => form.setData('description', event.target.value)}
                                        placeholder="Short customer-facing explanation of what the package includes."
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                    />
                                    <InputError message={form.errors.description} />
                                </div>

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="price">Price</Label>
                                        <Input
                                            id="price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={form.data.price}
                                            onChange={(event) => form.setData('price', event.target.value)}
                                            placeholder="1000"
                                        />
                                        <InputError message={form.errors.price} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="duration_minutes">Duration in minutes</Label>
                                        <Input
                                            id="duration_minutes"
                                            type="number"
                                            min="1"
                                            value={form.data.duration_minutes}
                                            onChange={(event) => form.setData('duration_minutes', event.target.value)}
                                            placeholder="60"
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
                                            placeholder="2048"
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
                                            placeholder="4096"
                                        />
                                        <InputError message={form.errors.speed_limit_kbps} />
                                    </div>
                                </div>

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                    />
                                    <div>
                                        <Label htmlFor="is_active">Package is active</Label>
                                        <p className="text-muted-foreground text-sm">
                                            Inactive packages stay in the system but stop appearing as live offers.
                                        </p>
                                    </div>
                                </div>
                                <InputError message={form.errors.is_active} />

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={form.processing || tenantOptions.length === 0} className="rounded-xl">
                                        {mode === 'create' ? 'Create package' : 'Save changes'}
                                    </Button>
                                    <Button asChild type="button" variant="outline" className="rounded-xl">
                                        <Link href={route('packages.index')}>Back to packages</Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Operational guidance</CardTitle>
                            <CardDescription>
                                These cues make the form closer to the real hotspot domain instead of a generic SaaS CRUD screen.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-center gap-2">
                                    <Package2 className="text-primary size-4" />
                                    <p className="font-medium">Selected type</p>
                                </div>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    {selectedType?.description ?? 'Choose a package type to guide validation and offer design.'}
                                </p>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="text-muted-foreground text-xs font-semibold tracking-[0.18em] uppercase">Tenant currency</p>
                                <div className="mt-3 flex items-center gap-2">
                                    <Badge variant="secondary" className="rounded-full px-3 py-1">
                                        {selectedTenant?.currency ?? 'No currency'}
                                    </Badge>
                                    <p className="text-muted-foreground text-sm">
                                        Currency follows the selected tenant to keep branch sales aligned.
                                    </p>
                                </div>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">What to enter</p>
                                <ul className="text-muted-foreground mt-3 space-y-2 text-sm">
                                    <li>Use `time` when access is sold by validity window only.</li>
                                    <li>Use `data` when the customer buys a capped usage bundle.</li>
                                    <li>Use `mixed` when both a duration and a cap should apply together.</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

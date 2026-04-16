import InputError from '@/components/input-error';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, MapPinned } from 'lucide-react';
import { FormEvent } from 'react';

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

interface ManagerOption {
    id: number;
    name: string;
    email: string;
    label: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface BranchPayload {
    id: number;
    tenant_id: number;
    name: string;
    code: string;
    status: string;
    location: string | null;
    address: string | null;
    manager_user_id: number | null;
}

interface BranchFormProps {
    mode: 'create' | 'edit';
    viewer: Viewer;
    tenantOptions: TenantOption[];
    managerOptions: ManagerOption[];
    statusOptions: StatusOption[];
    branch: BranchPayload | null;
}

export default function BranchForm({ mode, viewer, tenantOptions, managerOptions, statusOptions, branch }: BranchFormProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Branches', href: '/branches' },
        { title: mode === 'create' ? 'New Branch' : 'Edit Branch', href: '#' },
    ];

    const form = useForm({
        tenant_id: branch?.tenant_id?.toString() ?? tenantOptions[0]?.id?.toString() ?? '',
        name: branch?.name ?? '',
        code: branch?.code ?? '',
        status: branch?.status ?? statusOptions[0]?.value ?? 'active',
        location: branch?.location ?? '',
        address: branch?.address ?? '',
        manager_user_id: branch?.manager_user_id?.toString() ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const request = form.transform((data) => ({
            ...data,
            tenant_id: data.tenant_id ? Number(data.tenant_id) : null,
            manager_user_id: data.manager_user_id ? Number(data.manager_user_id) : null,
            address: data.address.trim() === '' ? null : data.address,
            location: data.location.trim() === '' ? null : data.location,
        }));

        if (mode === 'create') {
            request.post(route('branches.store'));
            return;
        }

        request.patch(route('branches.update', branch?.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'New Branch' : `Edit ${branch?.name ?? 'Branch'}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title={mode === 'create' ? 'Create Branch' : 'Edit Branch'}
                    description="Branches represent the real on-the-ground hotspot locations. This form handles their operational identity, assignment, and basic status."
                    viewer={viewer}
                />

                {tenantOptions.length === 0 && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>No tenant scope available</AlertTitle>
                        <AlertDescription>You need an assigned tenant before you can manage branches in this workspace.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>{mode === 'create' ? 'Branch details' : 'Update branch details'}</CardTitle>
                            <CardDescription>
                                Use this to register a branch code, assign a manager, and keep location data aligned with hotspot operations.
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
                                        <Label htmlFor="status">Status</Label>
                                        <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                                            <SelectTrigger id="status">
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.status} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Branch name</Label>
                                        <Input
                                            id="name"
                                            value={form.data.name}
                                            onChange={(event) => form.setData('name', event.target.value)}
                                            placeholder="Kariakoo Hub"
                                        />
                                        <InputError message={form.errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="code">Branch code</Label>
                                        <Input
                                            id="code"
                                            value={form.data.code}
                                            onChange={(event) => form.setData('code', event.target.value.toUpperCase())}
                                            placeholder="KRK"
                                        />
                                        <InputError message={form.errors.code} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="location">Location</Label>
                                        <Input
                                            id="location"
                                            value={form.data.location}
                                            onChange={(event) => form.setData('location', event.target.value)}
                                            placeholder="Dar es Salaam"
                                        />
                                        <InputError message={form.errors.location} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="manager_user_id">Manager</Label>
                                        <Select
                                            value={form.data.manager_user_id || 'none'}
                                            onValueChange={(value) => form.setData('manager_user_id', value === 'none' ? '' : value)}
                                        >
                                            <SelectTrigger id="manager_user_id">
                                                <SelectValue placeholder="Select manager" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">No manager assigned</SelectItem>
                                                {managerOptions.map((manager) => (
                                                    <SelectItem key={manager.id} value={manager.id.toString()}>
                                                        {manager.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.manager_user_id} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        value={form.data.address}
                                        onChange={(event) => form.setData('address', event.target.value)}
                                        placeholder="Aggrey Street, Kariakoo"
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                    />
                                    <InputError message={form.errors.address} />
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={form.processing || tenantOptions.length === 0} className="rounded-xl">
                                        {mode === 'create' ? 'Create branch' : 'Save changes'}
                                    </Button>
                                    <Button asChild type="button" variant="outline" className="rounded-xl">
                                        <Link href={route('branches.index')}>Back to branches</Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Operational guidance</CardTitle>
                            <CardDescription>
                                Branch records should stay practical and match how operators talk about real field locations.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-center gap-2">
                                    <MapPinned className="text-primary size-4" />
                                    <p className="font-medium">Branch identity</p>
                                </div>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Use short, memorable codes because branch codes tend to appear in operational references, exports, and support
                                    conversations.
                                </p>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Practical notes</p>
                                <ul className="text-muted-foreground mt-3 space-y-2 text-sm">
                                    <li>Set maintenance when the site is temporarily unavailable but should remain visible in history.</li>
                                    <li>Assign a manager when a real person owns day-to-day branch activity.</li>
                                    <li>Keep location and address understandable for both operators and finance teams.</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

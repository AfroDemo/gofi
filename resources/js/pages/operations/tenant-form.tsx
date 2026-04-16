import InputError from '@/components/input-error';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CircleAlert, Landmark } from 'lucide-react';
import { FormEvent } from 'react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface Option {
    value: string;
    label: string;
}

interface OwnerOption {
    id: number;
    name: string;
    email: string;
    label: string;
}

interface TenantPayload {
    id: number;
    name: string;
    slug: string;
    status: string;
    currency: string | null;
    country_code: string | null;
    timezone: string | null;
    owner_user_id: number | null;
    owner_name: string | null;
}

interface Capabilities {
    can_edit_owner: boolean;
    can_edit_status: boolean;
}

interface TenantFormProps {
    mode: 'create' | 'edit';
    viewer: Viewer;
    statusOptions: Option[];
    ownerOptions: OwnerOption[];
    tenant: TenantPayload | null;
    capabilities: Capabilities;
}

export default function TenantForm({ mode, viewer, statusOptions, ownerOptions, tenant, capabilities }: TenantFormProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Tenants', href: '/tenants' },
        { title: mode === 'create' ? 'New Tenant' : 'Edit Tenant', href: '#' },
    ];

    const form = useForm({
        name: tenant?.name ?? '',
        slug: tenant?.slug ?? '',
        status: tenant?.status ?? statusOptions[0]?.value ?? 'active',
        currency: tenant?.currency ?? 'TZS',
        country_code: tenant?.country_code ?? 'TZ',
        timezone: tenant?.timezone ?? 'Africa/Dar_es_Salaam',
        owner_user_id: tenant?.owner_user_id?.toString() ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const request = form.transform((data) => ({
            ...data,
            owner_user_id: data.owner_user_id ? Number(data.owner_user_id) : null,
            currency: data.currency.toUpperCase(),
            country_code: data.country_code.toUpperCase(),
        }));

        if (mode === 'create') {
            request.post(route('tenants.store'));
            return;
        }

        request.patch(route('tenants.update', tenant?.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'New Tenant' : `Edit ${tenant?.name ?? 'Tenant'}`} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title={mode === 'create' ? 'Create Tenant' : 'Edit Tenant'}
                    description="Tenants define who operates on the platform. This form covers the basics needed to activate a reseller and keep their workspace aligned."
                    viewer={viewer}
                />

                {mode === 'create' && !capabilities.can_edit_owner && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Tenant creation is restricted</AlertTitle>
                        <AlertDescription>Only platform admins can create new tenants from this workspace.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>{mode === 'create' ? 'Tenant details' : 'Update tenant details'}</CardTitle>
                            <CardDescription>Use this to keep the tenant identity, workspace defaults, and owner assignment aligned.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Tenant name</Label>
                                        <Input
                                            id="name"
                                            value={form.data.name}
                                            onChange={(event) => form.setData('name', event.target.value)}
                                            placeholder="CoastFi Networks"
                                        />
                                        <InputError message={form.errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="slug">Slug</Label>
                                        <Input
                                            id="slug"
                                            value={form.data.slug}
                                            onChange={(event) => form.setData('slug', event.target.value)}
                                            placeholder="coastfi-networks"
                                            disabled={!capabilities.can_edit_status && mode === 'edit'}
                                        />
                                        <InputError message={form.errors.slug} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="currency">Currency</Label>
                                        <Input
                                            id="currency"
                                            value={form.data.currency}
                                            onChange={(event) => form.setData('currency', event.target.value.toUpperCase())}
                                            placeholder="TZS"
                                            maxLength={3}
                                        />
                                        <InputError message={form.errors.currency} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="country_code">Country code</Label>
                                        <Input
                                            id="country_code"
                                            value={form.data.country_code}
                                            onChange={(event) => form.setData('country_code', event.target.value.toUpperCase())}
                                            placeholder="TZ"
                                            maxLength={2}
                                        />
                                        <InputError message={form.errors.country_code} />
                                    </div>
                                    <div className="space-y-2 xl:col-span-2">
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <Input
                                            id="timezone"
                                            value={form.data.timezone}
                                            onChange={(event) => form.setData('timezone', event.target.value)}
                                            placeholder="Africa/Dar_es_Salaam"
                                        />
                                        <InputError message={form.errors.timezone} />
                                    </div>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="status">Status</Label>
                                        {capabilities.can_edit_status ? (
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
                                        ) : (
                                            <div className="border-input bg-background flex h-10 items-center rounded-md border px-3 text-sm">
                                                {statusOptions.find((option) => option.value === form.data.status)?.label ?? form.data.status}
                                            </div>
                                        )}
                                        <InputError message={form.errors.status} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="owner_user_id">Owner</Label>
                                        {capabilities.can_edit_owner ? (
                                            <Select
                                                value={form.data.owner_user_id || 'none'}
                                                onValueChange={(value) => form.setData('owner_user_id', value === 'none' ? '' : value)}
                                            >
                                                <SelectTrigger id="owner_user_id">
                                                    <SelectValue placeholder="Select owner" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">No owner assigned</SelectItem>
                                                    {ownerOptions.map((option) => (
                                                        <SelectItem key={option.id} value={option.id.toString()}>
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <div className="border-input bg-background flex h-10 items-center rounded-md border px-3 text-sm">
                                                {tenant?.owner_name ?? 'Owner is managed by platform admins'}
                                            </div>
                                        )}
                                        <InputError message={form.errors.owner_user_id} />
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button
                                        type="submit"
                                        disabled={form.processing || (mode === 'create' && !capabilities.can_edit_owner)}
                                        className="rounded-xl"
                                    >
                                        {mode === 'create' ? 'Create tenant' : 'Save changes'}
                                    </Button>
                                    <Button asChild type="button" variant="outline" className="rounded-xl">
                                        <Link href={route('tenants.index')}>Back to tenants</Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Operational guidance</CardTitle>
                            <CardDescription>
                                These fields become defaults for the workspace, reporting, and future settlement behavior.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex items-center gap-2">
                                    <Landmark className="text-primary size-4" />
                                    <p className="font-medium">Workspace defaults</p>
                                </div>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Currency and timezone affect how this tenant experiences revenue reporting, payouts, and operational timestamps
                                    across the app.
                                </p>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="text-muted-foreground text-xs font-semibold tracking-[0.18em] uppercase">Status impact</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {statusOptions.map((option) => (
                                        <Badge
                                            key={option.value}
                                            variant={form.data.status === option.value ? 'default' : 'outline'}
                                            className="rounded-full px-3 py-1"
                                        >
                                            {option.label}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Practical notes</p>
                                <ul className="text-muted-foreground mt-3 space-y-2 text-sm">
                                    <li>Keep the slug stable once integrations or customer-facing links begin to rely on it.</li>
                                    <li>Owner assignment should reflect the person accountable for the tenant relationship.</li>
                                    <li>Use suspension when you need a commercial or operational pause without deleting history.</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

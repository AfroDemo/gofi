import { OpsFilters } from '@/components/ops/ops-filters';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, CircleCheckBig, MapPinned, PencilLine, Plus, Router, ShieldAlert, Store, Wrench } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Branches', href: '/branches' },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface Summary {
    total: number;
    active: number;
    unavailable: number;
    online_devices: number;
    open_incidents: number;
    open_follow_ups: number;
    active_sessions: number;
    successful_revenue: number;
}

interface Filters {
    search: string;
    status: string;
    attention: string;
    follow_up: string;
}

interface BranchRow {
    id: number;
    tenant: string | null;
    name: string;
    code: string;
    status: string;
    location: string | null;
    address: string | null;
    manager: string | null;
    manager_email: string | null;
    devices_count: number;
    online_devices_count: number;
    open_incidents_count: number;
    active_sessions_count: number;
    stale_pending_transactions_count: number;
    successful_revenue: number;
    currency: string | null;
    follow_up_status: string | null;
    follow_up_assignee: string | null;
    follow_up_owned_by_viewer: boolean;
    attention_reason: string | null;
}

interface BranchesPageProps {
    viewer: Viewer;
    filters: Filters;
    summary: Summary;
    branches: BranchRow[];
}

const tone: Record<string, string> = {
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    maintenance: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    inactive: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
};

export default function Branches({ viewer, filters, summary, branches }: BranchesPageProps) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Branches" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Branch Management"
                    description="Branches are where hotspot operations become real. This view keeps location, manager assignment, device footprint, and live commercial activity in one workspace."
                    viewer={viewer}
                />

                {flash?.success && (
                    <Alert className="border-emerald-500/30 bg-emerald-500/5 text-emerald-900 dark:text-emerald-100">
                        <CircleCheckBig className="size-4" />
                        <AlertTitle>Branch workflow updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                <OpsFilters
                    search={filters.search}
                    searchPlaceholder="Search branch name, code, location, manager, tenant, or address"
                    values={{ status: filters.status, attention: filters.attention, follow_up: filters.follow_up }}
                    fields={[
                        {
                            key: 'status',
                            label: 'Status',
                            placeholder: 'All statuses',
                            options: [
                                { label: 'All statuses', value: 'all' },
                                { label: 'Active', value: 'active' },
                                { label: 'Maintenance', value: 'maintenance' },
                                { label: 'Inactive', value: 'inactive' },
                            ],
                        },
                        {
                            key: 'attention',
                            label: 'Attention',
                            placeholder: 'All branches',
                            options: [
                                { label: 'All branches', value: 'all' },
                                { label: 'Needs review', value: 'review' },
                                { label: 'Unavailable only', value: 'unavailable' },
                                { label: 'Open incidents', value: 'open_incidents' },
                            ],
                        },
                        {
                            key: 'follow_up',
                            label: 'Follow-up',
                            placeholder: 'All follow-up states',
                            options: [
                                { label: 'All follow-ups', value: 'all' },
                                { label: 'Open follow-ups', value: 'open' },
                                { label: 'Assigned to me', value: 'mine' },
                                { label: 'Resolved', value: 'resolved' },
                                { label: 'No follow-up', value: 'none' },
                            ],
                        },
                    ]}
                    resultLabel={`${branches.length} branch matches in the current workspace.`}
                />

                <div className="flex justify-end">
                    <Button asChild className="rounded-xl">
                        <Link href={route('branches.create')}>
                            <Plus className="size-4" />
                            New branch
                        </Link>
                    </Button>
                </div>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard
                        label="Total branches"
                        value={summary.total.toString()}
                        hint="Operational hotspot points in the visible scope."
                        icon={Store}
                    />
                    <OpsStatCard
                        label="Active branches"
                        value={summary.active.toString()}
                        hint="Branches currently marked as active."
                        icon={MapPinned}
                    />
                    <OpsStatCard
                        label="Unavailable"
                        value={summary.unavailable.toString()}
                        hint="Branches in maintenance or inactive status."
                        icon={ShieldAlert}
                    />
                    <OpsStatCard
                        label="Open incidents"
                        value={summary.open_incidents.toString()}
                        hint="Unresolved device incidents across visible branches."
                        icon={Wrench}
                    />
                    <OpsStatCard
                        label="Open follow-ups"
                        value={summary.open_follow_ups.toString()}
                        hint="Branch investigations still marked as unresolved."
                        icon={ShieldAlert}
                    />
                    <OpsStatCard
                        label="Online devices"
                        value={summary.online_devices.toString()}
                        hint="Routers and devices reporting online now."
                        icon={Router}
                    />
                    <OpsStatCard
                        label="Active sessions"
                        value={summary.active_sessions.toString()}
                        hint="Customer sessions currently linked to these branches."
                        icon={Activity}
                    />
                    <OpsStatCard
                        label="Successful revenue"
                        value={formatMoney(summary.successful_revenue, viewer.currency)}
                        hint="Confirmed sales generated by these branches."
                        icon={CircleCheckBig}
                    />
                </section>

                <Card className="border-border/70">
                    <CardHeader>
                        <CardTitle>Branch list</CardTitle>
                        <CardDescription>Operational footprint and assignments for each visible branch.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {branches.length === 0 && (
                            <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                No branches are visible in this workspace yet.
                            </div>
                        )}
                        {branches.map((branch) => (
                            <div key={branch.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold">{branch.name}</p>
                                            <Badge variant="outline" className={tone[branch.status] ?? ''}>
                                                {branch.status}
                                            </Badge>
                                            <Badge variant="outline">{branch.code}</Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {[branch.tenant, branch.location, branch.address].filter(Boolean).join(' • ') ||
                                                'Branch metadata not set'}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Manager: {branch.manager ?? 'Unassigned'}
                                            {branch.manager_email ? ` • ${branch.manager_email}` : ''}
                                        </p>
                                        {branch.attention_reason && (
                                            <p className="text-sm text-amber-700 dark:text-amber-300">{branch.attention_reason}</p>
                                        )}
                                    </div>
                                    <div className="text-left xl:text-right">
                                        <p className="text-lg font-semibold">{formatMoney(branch.successful_revenue, branch.currency)}</p>
                                        <p className="text-muted-foreground text-sm">
                                            {branch.online_devices_count}/{branch.devices_count} devices online • {branch.active_sessions_count}{' '}
                                            active sessions
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-2 xl:justify-end">
                                            {branch.open_incidents_count > 0 && (
                                                <Badge variant="outline" className="bg-rose-500/10 text-rose-700 dark:text-rose-300">
                                                    {branch.open_incidents_count} open incidents
                                                </Badge>
                                            )}
                                            {branch.follow_up_status && (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        branch.follow_up_status === 'resolved'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                                    }
                                                >
                                                    {branch.follow_up_status === 'resolved' ? 'Follow-up resolved' : 'Follow-up open'}
                                                </Badge>
                                            )}
                                            {branch.follow_up_assignee && (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        branch.follow_up_owned_by_viewer
                                                            ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                                            : ''
                                                    }
                                                >
                                                    {branch.follow_up_owned_by_viewer
                                                        ? 'Assigned to you'
                                                        : `Assigned to ${branch.follow_up_assignee}`}
                                                </Badge>
                                            )}
                                            {branch.stale_pending_transactions_count > 0 && (
                                                <Badge variant="outline" className="bg-amber-500/10 text-amber-700 dark:text-amber-300">
                                                    {branch.stale_pending_transactions_count} stale pending payments
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="mt-3 flex flex-wrap gap-2 xl:justify-end">
                                            <Button asChild variant="outline" size="sm" className="rounded-lg">
                                                <Link href={route('branches.show', branch.id)}>Open detail</Link>
                                            </Button>
                                            <Button asChild variant="outline" size="sm" className="rounded-lg">
                                                <Link href={route('branches.edit', branch.id)}>
                                                    <PencilLine className="size-4" />
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

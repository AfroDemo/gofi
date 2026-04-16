import { OpsFilters } from '@/components/ops/ops-filters';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CircleOff, MapPinned, RadioTower, Router, Wrench } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Devices', href: '/devices' },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface Summary {
    total: number;
    online: number;
    offline: number;
    provisioning: number;
    branches_covered: number;
    open_incidents: number;
    open_follow_ups: number;
}

interface DeviceRow {
    id: number;
    tenant: string | null;
    branch: string | null;
    branch_status: string | null;
    location: string | null;
    name: string;
    identifier: string;
    status: string;
    integration_driver: string;
    ip_address: string | null;
    last_seen_at: string | null;
    open_incidents_count: number;
    follow_up_status: string | null;
    follow_up_assignee: string | null;
    follow_up_owned_by_viewer: boolean;
    attention_reason: string | null;
    metadata: Record<string, unknown> | null;
}

interface DevicesPageProps {
    viewer: Viewer;
    filters: {
        search: string;
        status: string;
        attention: string;
        follow_up: string;
    };
    summary: Summary;
    devices: DeviceRow[];
}

const tone: Record<string, string> = {
    online: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    offline: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    provisioning: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
};

export default function Devices({ viewer, filters, summary, devices }: DevicesPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Devices" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Device Operations"
                    description="Devices are the branch-side health layer behind captive portal sales. This view helps operators spot offline routers, provisioning gaps, and where sessions may be affected."
                    viewer={viewer}
                />

                <OpsFilters
                    search={filters.search}
                    searchPlaceholder="Search device name, identifier, branch, tenant, driver, or IP address"
                    values={{ status: filters.status, attention: filters.attention, follow_up: filters.follow_up }}
                    fields={[
                        {
                            key: 'status',
                            label: 'Status',
                            placeholder: 'All statuses',
                            options: [
                                { label: 'All statuses', value: 'all' },
                                { label: 'Online', value: 'online' },
                                { label: 'Offline', value: 'offline' },
                                { label: 'Provisioning', value: 'provisioning' },
                            ],
                        },
                        {
                            key: 'attention',
                            label: 'Attention',
                            placeholder: 'All devices',
                            options: [
                                { label: 'All devices', value: 'all' },
                                { label: 'Needs review', value: 'review' },
                                { label: 'Open incidents', value: 'open_incidents' },
                                { label: 'Offline only', value: 'offline' },
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
                    resultLabel={`${devices.length} device matches in the current workspace.`}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard label="Total devices" value={summary.total.toString()} hint="Routers and hotspot endpoints visible in scope." icon={Router} />
                    <OpsStatCard label="Online" value={summary.online.toString()} hint="Devices currently reporting in." icon={RadioTower} />
                    <OpsStatCard label="Offline" value={summary.offline.toString()} hint="Branch-side equipment that may block customer access." icon={CircleOff} />
                    <OpsStatCard label="Open incidents" value={summary.open_incidents.toString()} hint="Hardware issues that still need operator follow-up." icon={Wrench} />
                    <OpsStatCard
                        label="Open follow-ups"
                        value={summary.open_follow_ups.toString()}
                        hint="Devices currently assigned or waiting for operator follow-up."
                        icon={Wrench}
                    />
                    <OpsStatCard label="Provisioning" value={summary.provisioning.toString()} hint="Devices not fully ready for live operations yet." icon={Router} />
                    <OpsStatCard label="Branches covered" value={summary.branches_covered.toString()} hint="How many branches have visible hardware." icon={MapPinned} />
                </section>

                <Card className="border-border/70">
                    <CardHeader>
                        <CardTitle>Device health list</CardTitle>
                        <CardDescription>Use this to follow branch hardware state before blaming payment or session flows.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {devices.length === 0 && (
                            <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                No devices matched the current filters.
                            </div>
                        )}
                        {devices.map((device) => (
                            <div key={device.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold">{device.name}</p>
                                            <Badge variant="outline" className={tone[device.status] ?? ''}>
                                                {device.status}
                                            </Badge>
                                            <Badge variant="outline">{device.identifier}</Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {[device.tenant, device.branch, device.location].filter(Boolean).join(' • ') || 'Branch context unavailable'}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Driver: {device.integration_driver}
                                            {device.ip_address ? ` • ${device.ip_address}` : ' • No IP recorded'}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {device.follow_up_status && (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        device.follow_up_status === 'resolved'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-sky-500/10 text-sky-700 dark:text-sky-300'
                                                    }
                                                >
                                                    {device.follow_up_status === 'resolved' ? 'Follow-up resolved' : 'Follow-up open'}
                                                </Badge>
                                            )}
                                            {device.follow_up_assignee && (
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        device.follow_up_owned_by_viewer
                                                            ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                                            : ''
                                                    }
                                                >
                                                    {device.follow_up_owned_by_viewer
                                                        ? 'Assigned to you'
                                                        : `Assigned to ${device.follow_up_assignee}`}
                                                </Badge>
                                            )}
                                        </div>
                                        {device.attention_reason && (
                                            <p className="text-sm text-amber-700 dark:text-amber-300">{device.attention_reason}</p>
                                        )}
                                    </div>

                                    <div className="text-left xl:text-right">
                                        <p className="text-sm font-medium">Last seen</p>
                                        <p className="text-muted-foreground text-sm">{formatDateTime(device.last_seen_at)}</p>
                                        <Button asChild variant="outline" size="sm" className="mt-3 rounded-lg">
                                            <Link href={route('devices.show', device.id)}>Open detail</Link>
                                        </Button>
                                    </div>
                                </div>

                                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Operational hint</p>
                                        <p className="mt-2 font-medium">
                                            {device.status === 'online'
                                                ? 'Ready for live hotspot traffic.'
                                                : device.status === 'offline'
                                                  ? 'Investigate branch connectivity and power.'
                                                  : 'Finish provisioning before relying on this device.'}
                                        </p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Branch context</p>
                                        <p className="mt-2 font-medium">
                                            {device.branch_status ? `${device.branch_status} branch` : 'Branch status unavailable'}
                                        </p>
                                    </div>
                                    <div className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">Firmware / metadata</p>
                                        <p className="mt-2 font-medium">
                                            {device.open_incidents_count > 0
                                                ? `${device.open_incidents_count} open incident${device.open_incidents_count === 1 ? '' : 's'}`
                                                : device.follow_up_assignee
                                                  ? `Follow-up: ${device.follow_up_owned_by_viewer ? 'You' : device.follow_up_assignee}`
                                                  : String(device.metadata?.firmware ?? 'No metadata')}
                                        </p>
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

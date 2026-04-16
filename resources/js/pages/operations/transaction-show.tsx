import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BadgeDollarSign, DatabaseZap, HandCoins, Landmark, ReceiptText } from 'lucide-react';

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface RevenueRule {
    name: string;
    model: string;
    platform_percentage: number;
    platform_fixed_fee: number;
}

interface Allocation {
    model: string;
    gross_amount: number;
    gateway_fee: number;
    platform_amount: number;
    tenant_amount: number;
    snapshot: Record<string, unknown> | null;
}

interface CallbackRow {
    id: number;
    provider: string;
    event_type: string;
    callback_reference: string;
    payload: Record<string, unknown> | null;
    received_at: string | null;
    processed_at: string | null;
}

interface SessionRow {
    id: number;
    branch: string | null;
    package: string | null;
    mac_address: string;
    ip_address: string | null;
    status: string;
    started_at: string | null;
    expires_at: string | null;
    ended_at: string | null;
    data_used_mb: number;
}

interface LedgerRow {
    id: number;
    direction: string;
    entry_type: string;
    amount: number;
    currency: string;
    balance_after: number;
    description: string | null;
    posted_at: string | null;
}

interface TransactionDetail {
    id: number;
    reference: string;
    provider_reference: string | null;
    tenant: string | null;
    branch: string | null;
    location: string | null;
    package: string | null;
    voucher: string | null;
    initiated_by: string | null;
    source: string;
    status: string;
    phone_number: string | null;
    amount: number;
    gateway_fee: number;
    currency: string | null;
    confirmed_at: string | null;
    paid_at: string | null;
    created_at: string | null;
    metadata: Record<string, unknown> | null;
    revenue_rule: RevenueRule | null;
    allocation: Allocation | null;
    callbacks: CallbackRow[];
    sessions: SessionRow[];
    ledger_entries: LedgerRow[];
}

interface TransactionShowProps {
    viewer: Viewer;
    transaction: TransactionDetail;
}

const tone: Record<string, string> = {
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    voucher: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    mobile_money: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    manual: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
};

export default function TransactionShow({ viewer, transaction }: TransactionShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Transactions', href: '/transactions' },
        { title: transaction.reference, href: `/transactions/${transaction.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={transaction.reference} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex justify-start">
                    <Link
                        href={route('transactions.index')}
                        className="border-border/70 bg-background text-foreground inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium"
                    >
                        <ArrowLeft className="size-4" />
                        Back to transactions
                    </Link>
                </div>

                <OpsPageHeader
                    title={transaction.reference}
                    description="Transaction detail ties the payment stream to callback evidence, revenue allocation, and actual session fulfillment so operators can investigate what happened instead of guessing."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard
                        label="Gross amount"
                        value={formatMoney(transaction.amount, transaction.currency)}
                        hint="Customer-facing transaction value."
                        icon={BadgeDollarSign}
                    />
                    <OpsStatCard
                        label="Gateway fee"
                        value={formatMoney(transaction.gateway_fee, transaction.currency)}
                        hint="Processor cost attached to this payment."
                        icon={ReceiptText}
                    />
                    <OpsStatCard
                        label="Platform share"
                        value={formatMoney(transaction.allocation?.platform_amount ?? 0, transaction.currency)}
                        hint="Allocation kept by the platform rule."
                        icon={Landmark}
                    />
                    <OpsStatCard
                        label="Tenant share"
                        value={formatMoney(transaction.allocation?.tenant_amount ?? 0, transaction.currency)}
                        hint="Net amount attributed to the tenant."
                        icon={HandCoins}
                    />
                    <OpsStatCard
                        label="Callbacks"
                        value={transaction.callbacks.length.toString()}
                        hint="Provider callback events recorded for this payment."
                        icon={DatabaseZap}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Transaction summary</CardTitle>
                            <CardDescription>Commercial and operational context for this payment.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className={tone[transaction.source] ?? ''}>
                                    {transaction.source.replaceAll('_', ' ')}
                                </Badge>
                                <Badge variant="outline" className={tone[transaction.status] ?? ''}>
                                    {transaction.status}
                                </Badge>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2">
                                {[
                                    ['Tenant', transaction.tenant],
                                    ['Branch', transaction.branch],
                                    ['Location', transaction.location],
                                    ['Package', transaction.package],
                                    ['Voucher', transaction.voucher],
                                    ['Initiated by', transaction.initiated_by],
                                    ['Phone number', transaction.phone_number],
                                    ['Provider reference', transaction.provider_reference],
                                    ['Created', formatDateTime(transaction.created_at)],
                                    ['Paid at', formatDateTime(transaction.paid_at)],
                                    ['Confirmed', formatDateTime(transaction.confirmed_at)],
                                ].map(([label, value]) => (
                                    <div key={label} className="bg-muted/45 rounded-xl px-3 py-3">
                                        <p className="text-muted-foreground text-xs uppercase">{label}</p>
                                        <p className="mt-2 font-medium">{value || 'Not available'}</p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Revenue rule and allocation</CardTitle>
                            <CardDescription>Snapshot of how the payment was split when it became revenue.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {transaction.revenue_rule ? (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">{transaction.revenue_rule.name}</p>
                                    <p className="text-muted-foreground mt-1 text-sm capitalize">
                                        {transaction.revenue_rule.model.replaceAll('_', ' ')}
                                    </p>
                                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Platform percentage</p>
                                            <p className="mt-2 font-medium">{transaction.revenue_rule.platform_percentage}%</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Fixed fee</p>
                                            <p className="mt-2 font-medium">
                                                {formatMoney(transaction.revenue_rule.platform_fixed_fee, transaction.currency)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No revenue share rule was attached to this transaction.
                                </div>
                            )}

                            {transaction.allocation ? (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium capitalize">{transaction.allocation.model.replaceAll('_', ' ')} allocation</p>
                                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Gross amount</p>
                                            <p className="mt-2 font-medium">
                                                {formatMoney(transaction.allocation.gross_amount, transaction.currency)}
                                            </p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Gateway fee</p>
                                            <p className="mt-2 font-medium">
                                                {formatMoney(transaction.allocation.gateway_fee, transaction.currency)}
                                            </p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Platform amount</p>
                                            <p className="mt-2 font-medium">
                                                {formatMoney(transaction.allocation.platform_amount, transaction.currency)}
                                            </p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Tenant amount</p>
                                            <p className="mt-2 font-medium">
                                                {formatMoney(transaction.allocation.tenant_amount, transaction.currency)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    Allocation is not available yet. Pending and failed transactions can legitimately have no split.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Callback timeline</CardTitle>
                            <CardDescription>Provider events received for this transaction.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {transaction.callbacks.length === 0 && (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No provider callbacks were recorded for this transaction.
                                </div>
                            )}
                            {transaction.callbacks.map((callback) => (
                                <div key={callback.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium">{callback.event_type}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {callback.provider} • {callback.callback_reference}
                                            </p>
                                        </div>
                                        <div className="space-y-1 text-left text-sm xl:text-right">
                                            <p>Received: {formatDateTime(callback.received_at)}</p>
                                            <p className="text-muted-foreground">Processed: {formatDateTime(callback.processed_at)}</p>
                                        </div>
                                    </div>
                                    {callback.payload && (
                                        <pre className="bg-muted/45 mt-4 overflow-x-auto rounded-xl p-3 text-xs leading-6">
                                            {JSON.stringify(callback.payload, null, 2)}
                                        </pre>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Fulfilment and ledger</CardTitle>
                            <CardDescription>What happened after the payment, both in access control and tenant accounting.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-3">
                                <p className="font-medium">Hotspot sessions</p>
                                {transaction.sessions.length === 0 && (
                                    <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                        No access session has been linked to this transaction yet.
                                    </div>
                                )}
                                {transaction.sessions.map((session) => (
                                    <div key={session.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">{session.package ?? 'Package unavailable'}</p>
                                            <Badge variant="outline" className={tone[session.status] ?? ''}>
                                                {session.status}
                                            </Badge>
                                        </div>
                                        <p className="text-muted-foreground mt-2 text-sm">
                                            {[session.branch, session.ip_address, session.mac_address].filter(Boolean).join(' • ')}
                                        </p>
                                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                            <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                <p className="text-muted-foreground text-xs uppercase">Started</p>
                                                <p className="mt-2 font-medium">{formatDateTime(session.started_at)}</p>
                                            </div>
                                            <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                <p className="text-muted-foreground text-xs uppercase">Expires</p>
                                                <p className="mt-2 font-medium">{formatDateTime(session.expires_at)}</p>
                                            </div>
                                            <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                <p className="text-muted-foreground text-xs uppercase">Ended</p>
                                                <p className="mt-2 font-medium">{formatDateTime(session.ended_at)}</p>
                                            </div>
                                            <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                <p className="text-muted-foreground text-xs uppercase">Data used</p>
                                                <p className="mt-2 font-medium">{formatDataLimit(session.data_used_mb)}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="space-y-3">
                                <p className="font-medium">Ledger entries</p>
                                {transaction.ledger_entries.length === 0 && (
                                    <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                        No tenant ledger entries were generated for this transaction.
                                    </div>
                                )}
                                {transaction.ledger_entries.map((entry) => (
                                    <div key={entry.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="outline" className={tone[entry.direction] ?? ''}>
                                                {entry.direction}
                                            </Badge>
                                            <p className="font-medium">{entry.entry_type}</p>
                                        </div>
                                        <p className="mt-2 text-sm">{entry.description ?? 'No description recorded.'}</p>
                                        <p className="text-muted-foreground mt-2 text-sm">
                                            {formatMoney(entry.amount, entry.currency)} • balance after{' '}
                                            {formatMoney(entry.balance_after, entry.currency)} • {formatDateTime(entry.posted_at)}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            {transaction.metadata && (
                                <div className="space-y-2">
                                    <p className="font-medium">Captured metadata</p>
                                    <pre className="bg-muted/45 overflow-x-auto rounded-xl p-3 text-xs leading-6">
                                        {JSON.stringify(transaction.metadata, null, 2)}
                                    </pre>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}

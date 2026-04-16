import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { FollowUpNotesPanel, type FollowUpNoteRow } from '@/components/ops/follow-up-notes-panel';
import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDataLimit, formatDateTime, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, BadgeDollarSign, CircleAlert, DatabaseZap, HandCoins, Landmark, ReceiptText, RotateCcw } from 'lucide-react';
import { FormEvent } from 'react';

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
    pending_age_minutes: number | null;
    metadata: Record<string, unknown> | null;
    payment: {
        gateway: string | null;
        message: string | null;
        provider_reference: string | null;
        using_fallback: boolean;
        last_poll: {
            gateway: string | null;
            status: string | null;
            checked_at: string | null;
        };
        attempts: Array<{
            gateway: string | null;
            success: boolean;
            message: string | null;
        }>;
        can_check_status: boolean;
    };
    revenue_rule: RevenueRule | null;
    allocation: Allocation | null;
    callbacks: CallbackRow[];
    sessions: SessionRow[];
    ledger_entries: LedgerRow[];
    follow_up: {
        assigned_at: string | null;
        owned_by_viewer: boolean;
        assigned_user: { name: string; email: string } | null;
        assigned_by: { name: string; email: string } | null;
    } | null;
    notes: FollowUpNoteRow[];
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
    failed_payment: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    successful_payment: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
};

export default function TransactionShow({ viewer, transaction }: TransactionShowProps) {
    const { flash } = usePage<SharedData>().props;
    const noteForm = useForm({
        note: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Transactions', href: '/transactions' },
        { title: transaction.reference, href: `/transactions/${transaction.id}` },
    ];

    const submitNote = (event: FormEvent) => {
        event.preventDefault();
        noteForm.post(route('transactions.notes.store', transaction.id), {
            preserveScroll: true,
            onSuccess: () => noteForm.reset(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={transaction.reference} />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                {flash?.success && (
                    <Alert className="border-emerald-500/25 bg-emerald-500/8 text-emerald-900 dark:text-emerald-100">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Transaction updated</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert variant="destructive">
                        <CircleAlert className="size-4" />
                        <AlertTitle>Refresh issue</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

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
                                {transaction.pending_age_minutes && (
                                    <Badge variant="outline" className={tone.pending}>
                                        pending {transaction.pending_age_minutes} min
                                    </Badge>
                                )}
                            </div>

                            {transaction.payment.can_check_status && (
                                <div className="flex justify-start">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-xl"
                                        onClick={() => router.post(route('transactions.refresh-status', transaction.id))}
                                    >
                                        <RotateCcw className="size-4" />
                                        Re-check payment status
                                    </Button>
                                </div>
                            )}

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
                            <CardTitle>Payment diagnostics</CardTitle>
                            <CardDescription>Gateway context for pending, failed, or delayed payment troubleshooting.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Gateway</p>
                                    <p className="mt-2 font-medium">{transaction.payment.gateway || 'Not recorded'}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Provider reference</p>
                                    <p className="mt-2 font-medium">{transaction.payment.provider_reference || 'Not available'}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Last status check</p>
                                    <p className="mt-2 font-medium">{formatDateTime(transaction.payment.last_poll.checked_at)}</p>
                                </div>
                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                    <p className="text-muted-foreground text-xs uppercase">Last polled status</p>
                                    <p className="mt-2 font-medium">{transaction.payment.last_poll.status || 'Not checked yet'}</p>
                                </div>
                            </div>

                            <div className="border-border/60 rounded-2xl border px-4 py-4">
                                <p className="font-medium">Operator guidance</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-6">
                                    {transaction.status === 'pending'
                                        ? transaction.pending_age_minutes && transaction.pending_age_minutes >= 5
                                            ? 'This payment has been pending for several minutes. Re-check status, verify the customer number, and inspect the callback timeline below.'
                                            : 'This payment is still pending. Re-check status if the customer says they already approved the prompt.'
                                        : transaction.status === 'successful'
                                          ? 'Payment is confirmed. Use the fulfilment and ledger sections below to verify access and tenant accounting.'
                                          : 'This payment did not complete. Review gateway attempts and callback evidence before asking the customer to retry.'}
                                </p>
                            </div>

                            {transaction.payment.message && (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">Gateway message</p>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{transaction.payment.message}</p>
                                </div>
                            )}

                            {transaction.payment.attempts.length > 0 ? (
                                <div className="border-border/60 rounded-2xl border px-4 py-4">
                                    <p className="font-medium">Gateway attempts</p>
                                    <div className="mt-4 space-y-3">
                                        {transaction.payment.attempts.map((attempt, index) => (
                                            <div key={`${attempt.gateway}-${index}`} className="bg-muted/45 rounded-xl px-3 py-3">
                                                <div className="flex items-center justify-between gap-3">
                                                    <p className="font-medium">{attempt.gateway || 'Unknown gateway'}</p>
                                                    <Badge variant="outline" className={attempt.success ? tone.successful_payment : tone.failed_payment}>
                                                        {attempt.success ? 'accepted' : 'failed'}
                                                    </Badge>
                                                </div>
                                                <p className="text-muted-foreground mt-2 text-sm">{attempt.message || 'No message recorded for this attempt.'}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                                    No gateway attempts were captured in metadata for this transaction.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
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

                <FollowUpNotesPanel
                    title="Operator follow-up notes"
                    description="Record who picked up this payment issue, what checks were done, and what the next action should be."
                    notes={transaction.notes}
                    followUp={transaction.follow_up}
                    takeOwnershipHref={route('transactions.follow-up.store', transaction.id)}
                    releaseOwnershipHref={route('transactions.follow-up.destroy', transaction.id)}
                    note={noteForm.data.note}
                    onNoteChange={(value) => noteForm.setData('note', value)}
                    onSubmit={submitNote}
                    error={noteForm.errors.note}
                    processing={noteForm.processing}
                    emptyMessage="No follow-up notes have been recorded for this transaction yet."
                />

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

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDataLimit, formatDateTime, formatMinutes, formatMoney } from '@/lib/formatters';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, RotateCcw, Smartphone, Ticket, Wifi } from 'lucide-react';

interface TenantInfo {
    name: string;
    slug: string;
}

interface BranchInfo {
    name: string;
    code: string;
    location: string | null;
}

interface SessionInfo {
    status: string;
    device_mac_address: string;
    device_ip_address: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    started_at: string | null;
    expires_at: string | null;
    ended_at: string | null;
}

interface TransactionInfo {
    reference: string;
    source: string;
    status: string;
    state_hint: string;
    pending_age_minutes: number | null;
    phone_number: string | null;
    amount: number;
    currency: string | null;
    package: string | null;
    voucher_code: string | null;
    created_at: string | null;
    confirmed_at: string | null;
    payment: {
        gateway: string | null;
        provider_reference: string | null;
        message: string | null;
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
        can_restart: boolean;
    };
    session: SessionInfo | null;
}

interface PortalTransactionStatusProps {
    tenant: TenantInfo;
    branch: BranchInfo;
    support: {
        network_name: string;
        contact_name: string | null;
        contact_email: string | null;
        contact_role: string;
        branch_label: string;
    };
    guidance: string[];
    transaction: TransactionInfo;
}

const tone: Record<string, string> = {
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    voucher: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    mobile_money: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    expired: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
};

export default function PortalTransactionStatus({ tenant, branch, support, guidance, transaction }: PortalTransactionStatusProps) {
    const { flash } = usePage<SharedData>().props;

    const primaryMessage =
        transaction.state_hint === 'access_active'
            ? transaction.source === 'voucher'
                ? 'Voucher accepted and access session started.'
                : 'Payment confirmed and access is active for this customer.'
            : transaction.state_hint === 'payment_confirmed'
              ? 'Payment confirmed and the transaction is complete.'
              : transaction.state_hint === 'stale_pending'
                ? 'This payment request has been pending for several minutes and may need a refresh or retry.'
                : transaction.state_hint === 'awaiting_confirmation'
              ? 'Payment request is waiting for customer approval or provider callback.'
              : transaction.state_hint === 'cancelled'
                ? 'This payment was cancelled before it completed.'
                : 'This transaction did not complete successfully.';

    return (
        <>
            <Head title={transaction.reference} />

            <div className="bg-background text-foreground relative min-h-screen overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[24rem] bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.16),transparent_34%),radial-gradient(circle_at_top_right,rgba(13,148,136,0.12),transparent_28%)]" />
                <div className="gofi-grid absolute inset-0 opacity-35" />

                <div className="relative mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-5 sm:px-6 lg:px-8">
                    <header className="gofi-shell px-5 py-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="bg-primary text-primary-foreground flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg">
                                    <Wifi className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-lg font-semibold tracking-tight">{tenant.name}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {branch.name} ({branch.code}) status view
                                    </p>
                                </div>
                            </div>

                            <Button asChild variant="outline" className="rounded-xl">
                                <Link href={route('portal.show', { tenantSlug: tenant.slug, branchCode: branch.code })}>
                                    <RotateCcw className="mr-2 size-4" />
                                    Back to portal
                                </Link>
                            </Button>
                        </div>
                    </header>

                    <main className="mt-6 flex flex-1 flex-col gap-6">
                        {flash?.success && (
                            <Alert className="border-emerald-500/25 bg-emerald-500/8 text-emerald-900 dark:text-emerald-100">
                                <CheckCircle2 className="size-4" />
                                <AlertTitle>Portal update</AlertTitle>
                                <AlertDescription>{flash.success}</AlertDescription>
                            </Alert>
                        )}

                        {flash?.error && (
                            <Alert variant="destructive">
                                <Ticket className="size-4" />
                                <AlertTitle>Payment issue</AlertTitle>
                                <AlertDescription>{flash.error}</AlertDescription>
                            </Alert>
                        )}

                        <section className="gofi-shell px-6 py-7 sm:px-8">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" className={tone[transaction.source] ?? ''}>
                                    {transaction.source.replaceAll('_', ' ')}
                                </Badge>
                                <Badge variant="outline" className={tone[transaction.status] ?? ''}>
                                    {transaction.status}
                                </Badge>
                                {transaction.session && (
                                    <Badge variant="outline" className={tone[transaction.session.status] ?? ''}>
                                        session {transaction.session.status}
                                    </Badge>
                                )}
                            </div>

                            <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{transaction.reference}</h1>
                            <p className="text-muted-foreground mt-3 max-w-2xl text-sm leading-7 sm:text-base">{primaryMessage}</p>

                            <div className="mt-5 rounded-2xl border border-dashed px-4 py-4">
                                <p className="text-muted-foreground text-xs font-semibold tracking-[0.2em] uppercase">Branch support</p>
                                <p className="mt-2 font-medium">{support.contact_name || 'Support team'}</p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {support.contact_role}
                                    {support.contact_email ? ` • ${support.contact_email}` : ''}
                                </p>
                            </div>

                            <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div className="gofi-surface px-4 py-4">
                                    <p className="text-muted-foreground text-xs uppercase">Amount</p>
                                    <p className="mt-2 text-lg font-semibold">{formatMoney(transaction.amount, transaction.currency)}</p>
                                </div>
                                <div className="gofi-surface px-4 py-4">
                                    <p className="text-muted-foreground text-xs uppercase">Package</p>
                                    <p className="mt-2 font-medium">{transaction.package || 'Not attached'}</p>
                                </div>
                                <div className="gofi-surface px-4 py-4">
                                    <p className="text-muted-foreground text-xs uppercase">Phone</p>
                                    <p className="mt-2 font-medium">{transaction.phone_number || 'Not used'}</p>
                                </div>
                                <div className="gofi-surface px-4 py-4">
                                    <p className="text-muted-foreground text-xs uppercase">Voucher</p>
                                    <p className="mt-2 font-medium">{transaction.voucher_code || 'Not used'}</p>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                {transaction.payment.can_check_status && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-xl"
                                        onClick={() =>
                                            router.post(
                                                route('portal.transactions.refresh', {
                                                    tenantSlug: tenant.slug,
                                                    branchCode: branch.code,
                                                    reference: transaction.reference,
                                                })
                                            )
                                        }
                                    >
                                        Check payment status
                                    </Button>
                                )}

                                {transaction.payment.can_restart && (
                                    <Button asChild className="rounded-xl">
                                        <Link href={route('portal.show', { tenantSlug: tenant.slug, branchCode: branch.code })}>
                                            Start a new request
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </section>

                        <section className="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                            <Card className="border-border/70 bg-card/88 backdrop-blur">
                                <CardHeader>
                                    <CardTitle>What to do next</CardTitle>
                                    <CardDescription>Use the status below to decide whether to wait, retry, or let the customer browse.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {transaction.state_hint === 'awaiting_confirmation' ? (
                                        <div className="rounded-2xl border border-amber-500/25 bg-amber-500/8 px-4 py-4">
                                            <div className="flex items-start gap-3">
                                                <Clock3 className="mt-0.5 size-5 text-amber-700 dark:text-amber-300" />
                                                <div>
                                                    <p className="font-medium">Waiting for mobile-money confirmation</p>
                                                    <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                        Ask the customer to check their phone and approve the payment prompt. If the prompt does not arrive,
                                                        return to the portal and start a new request with the correct number.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : transaction.state_hint === 'stale_pending' ? (
                                        <div className="rounded-2xl border border-orange-500/25 bg-orange-500/8 px-4 py-4">
                                            <div className="flex items-start gap-3">
                                                <Clock3 className="mt-0.5 size-5 text-orange-700 dark:text-orange-300" />
                                                <div>
                                                    <p className="font-medium">Request may be stuck or delayed</p>
                                                    <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                        This request has been pending for about {transaction.pending_age_minutes} minutes. Check payment status
                                                        first. If nothing changes, start a new request from the portal with the correct number.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : transaction.status === 'successful' ? (
                                        <div className="rounded-2xl border border-emerald-500/25 bg-emerald-500/8 px-4 py-4">
                                            <div className="flex items-start gap-3">
                                                <CheckCircle2 className="mt-0.5 size-5 text-emerald-700 dark:text-emerald-300" />
                                                <div>
                                                    <p className="font-medium">Transaction completed</p>
                                                    <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                        This sale is marked successful. If a session is active below, the customer can continue browsing without
                                                        any extra step on this screen.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : transaction.state_hint === 'cancelled' ? (
                                        <div className="rounded-2xl border border-slate-500/25 bg-slate-500/8 px-4 py-4">
                                            <div className="flex items-start gap-3">
                                                <Ticket className="mt-0.5 size-5 text-slate-700 dark:text-slate-300" />
                                                <div>
                                                    <p className="font-medium">Transaction was cancelled</p>
                                                    <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                        The customer can start a fresh payment request from the portal whenever they are ready to try again.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="rounded-2xl border border-rose-500/25 bg-rose-500/8 px-4 py-4">
                                            <div className="flex items-start gap-3">
                                                <Ticket className="mt-0.5 size-5 text-rose-700 dark:text-rose-300" />
                                                <div>
                                                    <p className="font-medium">Transaction needs a retry</p>
                                                    <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                        No access was granted from this attempt. Go back to the portal and retry with a different package,
                                                        payment number, or voucher code.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {[
                                            ['Branch', branch.location || branch.name],
                                            ['Created', formatDateTime(transaction.created_at)],
                                            ['Confirmed', formatDateTime(transaction.confirmed_at)],
                                            ['Source', transaction.source.replaceAll('_', ' ')],
                                            ['Gateway', transaction.payment.gateway?.replaceAll('_', ' ') || 'Not recorded'],
                                        ].map(([label, value]) => (
                                            <div key={label} className="bg-muted/45 rounded-xl px-3 py-3">
                                                <p className="text-muted-foreground text-xs uppercase">{label}</p>
                                                <p className="mt-2 font-medium">{value}</p>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="rounded-2xl border border-dashed px-4 py-4">
                                        <p className="font-medium">Customer guidance</p>
                                        <div className="mt-3 space-y-2">
                                            {guidance.slice(0, 3).map((item, index) => (
                                                <p key={`${index}-${item}`} className="text-muted-foreground text-sm leading-6">
                                                    {item}
                                                </p>
                                            ))}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-border/70 bg-card/88 backdrop-blur">
                                <CardHeader>
                                    <CardTitle>Payment diagnostics</CardTitle>
                                    <CardDescription>Useful status details for slow callbacks, retries, and branch-side support.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Provider reference</p>
                                            <p className="mt-2 font-medium">{transaction.payment.provider_reference || 'Not available'}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Last provider check</p>
                                            <p className="mt-2 font-medium">{formatDateTime(transaction.payment.last_poll.checked_at)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Last poll status</p>
                                            <p className="mt-2 font-medium">{transaction.payment.last_poll.status || 'Not checked yet'}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Fallback used</p>
                                            <p className="mt-2 font-medium">{transaction.payment.using_fallback ? 'Yes' : 'No'}</p>
                                        </div>
                                    </div>

                                    {transaction.payment.message && (
                                        <div className="rounded-2xl border border-dashed px-4 py-4">
                                            <p className="text-muted-foreground text-xs uppercase">Gateway message</p>
                                            <p className="mt-2 text-sm leading-6">{transaction.payment.message}</p>
                                        </div>
                                    )}

                                    {transaction.payment.attempts.length > 0 && (
                                        <div className="space-y-3">
                                            <p className="text-sm font-medium">Gateway attempts</p>
                                            {transaction.payment.attempts.map((attempt, index) => (
                                                <div key={`${attempt.gateway}-${index}`} className="border-border/60 rounded-2xl border px-4 py-4">
                                                    <div className="flex items-center justify-between gap-3">
                                                        <p className="font-medium">{attempt.gateway || 'Unknown gateway'}</p>
                                                        <Badge variant="outline" className={attempt.success ? tone.successful : tone.failed}>
                                                            {attempt.success ? 'accepted' : 'failed'}
                                                        </Badge>
                                                    </div>
                                                    <p className="text-muted-foreground mt-2 text-sm leading-6">
                                                        {attempt.message || 'No gateway message recorded for this attempt.'}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-border/70 bg-card/88 backdrop-blur">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Smartphone className="size-5" />
                                        Access session
                                    </CardTitle>
                                    <CardDescription>Session details appear here when voucher access or payment fulfillment activates service.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {transaction.session ? (
                                        <div className="space-y-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge variant="outline" className={tone[transaction.session.status] ?? ''}>
                                                    {transaction.session.status}
                                                </Badge>
                                            </div>

                                            <div className="grid gap-3 sm:grid-cols-2">
                                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                    <p className="text-muted-foreground text-xs uppercase">Started</p>
                                                    <p className="mt-2 font-medium">{formatDateTime(transaction.session.started_at)}</p>
                                                </div>
                                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                    <p className="text-muted-foreground text-xs uppercase">Expires</p>
                                                    <p className="mt-2 font-medium">{formatDateTime(transaction.session.expires_at)}</p>
                                                </div>
                                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                    <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                                    <p className="mt-2 font-medium">{formatMinutes(transaction.session.duration_minutes)}</p>
                                                </div>
                                                <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                    <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                                    <p className="mt-2 font-medium">{formatDataLimit(transaction.session.data_limit_mb)}</p>
                                                </div>
                                                <div className="bg-muted/45 rounded-xl px-3 py-3 sm:col-span-2">
                                                    <p className="text-muted-foreground text-xs uppercase">Device</p>
                                                    <p className="mt-2 font-medium">{transaction.session.device_ip_address || transaction.session.device_mac_address}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm leading-6">
                                            No access session is attached yet. This is expected while a mobile-money payment is still pending.
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}

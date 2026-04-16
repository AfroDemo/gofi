import { OpsPageHeader } from '@/components/ops/ops-page-header';
import { OpsStatCard } from '@/components/ops/ops-stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BadgeDollarSign, CircleAlert, CircleOff, HandCoins, WalletCards } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
];

interface Viewer {
    scope: 'platform' | 'tenant';
    name: string;
    role: string;
    currency?: string | null;
}

interface Summary {
    gross_successful: number;
    pending_count: number;
    failed_count: number;
    platform_share: number;
    tenant_share: number;
}

interface SourceMixRow {
    source: string;
    count: number;
    amount: number;
}

interface TransactionRow {
    id: number;
    reference: string;
    tenant: string | null;
    branch: string | null;
    package: string | null;
    initiated_by: string | null;
    source: string;
    status: string;
    phone_number: string | null;
    amount: number;
    gateway_fee: number;
    currency: string | null;
    platform_amount: number;
    tenant_amount: number;
    confirmed_at: string | null;
    created_at: string | null;
}

interface TransactionsPageProps {
    viewer: Viewer;
    summary: Summary;
    sourceMix: SourceMixRow[];
    transactions: TransactionRow[];
}

const tone: Record<string, string> = {
    successful: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    cancelled: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    voucher: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    mobile_money: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    manual: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
};

export default function Transactions({ viewer, summary, sourceMix, transactions }: TransactionsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transactions" />
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-4">
                <OpsPageHeader
                    title="Transactions"
                    description="This is the start of the real payments and settlements surface: confirmed sales, pending callbacks, channel mix, and revenue splits all come from our actual domain records."
                    viewer={viewer}
                />

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <OpsStatCard
                        label="Successful gross"
                        value={formatMoney(summary.gross_successful, viewer.currency)}
                        hint="Confirmed sales before payout settlement."
                        icon={BadgeDollarSign}
                    />
                    <OpsStatCard
                        label="Pending callbacks"
                        value={summary.pending_count.toString()}
                        hint="Transactions still waiting on payment confirmation."
                        icon={CircleAlert}
                    />
                    <OpsStatCard
                        label="Failed payments"
                        value={summary.failed_count.toString()}
                        hint="Attempts that did not convert to access."
                        icon={CircleOff}
                    />
                    <OpsStatCard
                        label="Platform share"
                        value={formatMoney(summary.platform_share, viewer.currency)}
                        hint="Revenue captured by the platform allocation rules."
                        icon={WalletCards}
                    />
                    <OpsStatCard
                        label="Tenant share"
                        value={formatMoney(summary.tenant_share, viewer.currency)}
                        hint="Net amount owed or credited to tenants."
                        icon={HandCoins}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Payment stream</CardTitle>
                            <CardDescription>Latest transaction records across mobile money and voucher-based access.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {transactions.map((transaction) => (
                                <div key={transaction.id} className="border-border/60 rounded-2xl border px-4 py-4">
                                    <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold">{transaction.reference}</p>
                                                <Badge variant="outline" className={tone[transaction.source] ?? ''}>
                                                    {transaction.source.replaceAll('_', ' ')}
                                                </Badge>
                                                <Badge variant="outline" className={tone[transaction.status] ?? ''}>
                                                    {transaction.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {[transaction.package, transaction.branch, transaction.tenant].filter(Boolean).join(' • ') ||
                                                    'Unassigned transaction'}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                Initiated by {transaction.initiated_by ?? 'Unknown operator'}
                                                {transaction.phone_number ? ` • ${transaction.phone_number}` : ''}
                                            </p>
                                        </div>
                                        <div className="text-left xl:text-right">
                                            <p className="text-lg font-semibold">{formatMoney(transaction.amount, transaction.currency)}</p>
                                            <p className="text-muted-foreground text-sm">
                                                Gateway fee {formatMoney(transaction.gateway_fee, transaction.currency)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Platform split</p>
                                            <p className="mt-2 font-medium">{formatMoney(transaction.platform_amount, transaction.currency)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Tenant split</p>
                                            <p className="mt-2 font-medium">{formatMoney(transaction.tenant_amount, transaction.currency)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Created</p>
                                            <p className="mt-2 font-medium">{formatDateTime(transaction.created_at)}</p>
                                        </div>
                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                            <p className="text-muted-foreground text-xs uppercase">Confirmed</p>
                                            <p className="mt-2 font-medium">{formatDateTime(transaction.confirmed_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-border/70">
                        <CardHeader>
                            <CardTitle>Channel mix</CardTitle>
                            <CardDescription>Which payment rails are carrying the current sales volume.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {sourceMix.map((item) => (
                                <div key={item.source} className="border-border/60 flex items-center justify-between rounded-xl border px-4 py-3">
                                    <div>
                                        <p className="font-medium capitalize">{item.source.replaceAll('_', ' ')}</p>
                                        <p className="text-muted-foreground text-sm">{item.count} transactions in current scope.</p>
                                    </div>
                                    <p className="font-semibold">{formatMoney(item.amount, viewer.currency)}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}

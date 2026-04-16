import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BadgeDollarSign, Building2, CreditCard, RadioTower, Router, ShieldCheck, Ticket, WalletCards, Wifi } from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome">
                <meta
                    name="description"
                    content="Go-Fi helps operators and resellers sell hotspot access with mobile money, vouchers, tenant controls, and revenue sharing."
                />
            </Head>

            <div className="bg-background text-foreground relative min-h-screen overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[32rem] bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.16),transparent_36%),radial-gradient(circle_at_top_right,rgba(13,148,136,0.12),transparent_30%)]" />
                <div className="gofi-grid absolute inset-0 opacity-35" />

                <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-8">
                    <header className="gofi-shell mb-8 px-5 py-4">
                        <nav className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="bg-primary text-primary-foreground shadow-primary/20 flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg">
                                    <Wifi className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-lg font-semibold tracking-tight">Go-Fi</p>
                                    <p className="text-muted-foreground text-sm">Multi-tenant hotspot billing and reseller platform</p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <span className="border-border/70 bg-card/80 text-muted-foreground rounded-full border px-3 py-1 text-xs font-semibold tracking-[0.22em] uppercase">
                                    Mobile-money first
                                </span>
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold shadow-lg transition"
                                    >
                                        Open dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="border-border/70 bg-card/70 hover:bg-card inline-flex h-11 items-center justify-center rounded-xl border px-5 text-sm font-medium transition"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold shadow-lg transition"
                                        >
                                            Create account
                                        </Link>
                                    </>
                                )}
                            </div>
                        </nav>
                    </header>

                    <main className="flex flex-1 flex-col gap-8">
                        <section className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <div className="gofi-shell px-6 py-8 sm:px-8 sm:py-10">
                                <div className="max-w-3xl">
                                    <span className="border-primary/15 bg-primary/8 text-primary inline-flex rounded-full border px-3 py-1 text-xs font-semibold tracking-[0.22em] uppercase">
                                        Built for real hotspot operations
                                    </span>
                                    <h1 className="mt-5 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                                        Sell internet access, manage partners, and track revenue in one platform.
                                    </h1>
                                    <p className="text-muted-foreground mt-5 max-w-2xl text-base leading-7 sm:text-lg">
                                        Go-Fi brings together mobile payments, voucher fallback, tenant isolation, branch control, session tracking,
                                        and revenue sharing for hotspot operators in mobile-money-first markets.
                                    </p>
                                </div>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    {auth.user ? (
                                        <Link
                                            href={route('dashboard')}
                                            className="bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 inline-flex h-12 items-center justify-center rounded-xl px-5 text-sm font-semibold shadow-lg transition"
                                        >
                                            Go to operations
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    ) : (
                                        <>
                                            <Link
                                                href={route('login')}
                                                className="bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 inline-flex h-12 items-center justify-center rounded-xl px-5 text-sm font-semibold shadow-lg transition"
                                            >
                                                Start in the dashboard
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </Link>
                                            <Link
                                                href={route('register')}
                                                className="border-border/70 bg-card/70 hover:bg-card inline-flex h-12 items-center justify-center rounded-xl border px-5 text-sm font-medium transition"
                                            >
                                                Create a new operator account
                                            </Link>
                                        </>
                                    )}
                                </div>

                                <div className="mt-8 grid gap-4 sm:grid-cols-3">
                                    {[
                                        ['Tenants and branches', 'Run one network or many partner-owned points.', Building2],
                                        ['Payments and vouchers', 'Accept mobile money or activate fallback vouchers.', WalletCards],
                                        ['Sessions and finance', 'Track access, allocations, and payout readiness.', BadgeDollarSign],
                                    ].map(([title, description, Icon]) => (
                                        <div key={title} className="gofi-surface px-4 py-4">
                                            <div className="bg-primary/10 text-primary mb-3 flex h-10 w-10 items-center justify-center rounded-xl">
                                                <Icon className="h-5 w-5" />
                                            </div>
                                            <p className="font-medium">{title}</p>
                                            <p className="text-muted-foreground mt-1 text-sm leading-6">{description}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div className="gofi-shell px-6 py-6">
                                    <p className="text-muted-foreground text-xs font-semibold tracking-[0.24em] uppercase">Current system baseline</p>
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                                        {[
                                            ['Multi-tenant foundation', 'Tenants, memberships, branches, devices, and packages.', ShieldCheck],
                                            ['Billing foundation', 'Transactions, callbacks, revenue allocation, payouts, and ledger.', CreditCard],
                                            ['Access foundation', 'Voucher lifecycle plus hotspot sessions and expiry tracking.', Ticket],
                                        ].map(([title, description, Icon]) => (
                                            <div key={title} className="border-border/70 bg-card/70 rounded-2xl border px-4 py-4">
                                                <div className="flex items-start gap-3">
                                                    <div className="bg-accent text-primary mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl">
                                                        <Icon className="h-4 w-4" />
                                                    </div>
                                                    <div>
                                                        <p className="font-medium">{title}</p>
                                                        <p className="text-muted-foreground mt-1 text-sm leading-6">{description}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="gofi-shell px-6 py-6">
                                    <p className="text-muted-foreground text-xs font-semibold tracking-[0.24em] uppercase">Implementation stance</p>
                                    <div className="mt-4 space-y-4">
                                        <div className="border-border/70 bg-card/70 rounded-2xl border px-4 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="bg-primary/10 text-primary flex h-10 w-10 items-center justify-center rounded-xl">
                                                    <Router className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p className="font-medium">Design adapted to the real app</p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        We are using the visual direction, not the demo-only role switching or fake flows.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-border/70 bg-card/70 rounded-2xl border px-4 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="bg-accent text-primary flex h-10 w-10 items-center justify-center rounded-xl">
                                                    <RadioTower className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p className="font-medium">Operator-first UX</p>
                                                    <p className="text-muted-foreground mt-1 text-sm">
                                                        Clear dashboards, realistic finance views, and a mobile-first captive portal come before
                                                        decoration.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section className="grid gap-6 lg:grid-cols-3">
                            {[
                                {
                                    title: 'Platform administration',
                                    icon: Building2,
                                    description:
                                        'Manage partners, review branch activity, monitor revenue, and track payout obligations across the whole platform.',
                                },
                                {
                                    title: 'Tenant operations',
                                    icon: CreditCard,
                                    description:
                                        'Control local branches, packages, vouchers, device readiness, and transaction visibility for each reseller.',
                                },
                                {
                                    title: 'Customer access flow',
                                    icon: Wifi,
                                    description:
                                        'Guide customers through package selection, mobile payment, voucher redemption, and session activation on low-end mobile browsers.',
                                },
                            ].map(({ title, icon: Icon, description }) => (
                                <div key={title} className="gofi-shell px-6 py-6">
                                    <div className="bg-primary/10 text-primary flex h-11 w-11 items-center justify-center rounded-2xl">
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <h2 className="mt-4 text-lg font-semibold">{title}</h2>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{description}</p>
                                </div>
                            ))}
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}

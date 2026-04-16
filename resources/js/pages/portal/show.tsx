import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatDataLimit, formatMinutes, formatMoney, formatSpeed } from '@/lib/formatters';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CircleAlert, CreditCard, MapPin, Smartphone, Ticket, Wifi } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface TenantInfo {
    name: string;
    slug: string;
    currency: string | null;
}

interface BranchInfo {
    name: string;
    code: string;
    location: string | null;
    address: string | null;
}

interface PackageCard {
    id: number;
    name: string;
    description: string | null;
    price: number;
    currency: string | null;
    duration_minutes: number | null;
    data_limit_mb: number | null;
    speed_limit_kbps: number | null;
    is_branch_specific: boolean;
}

interface PortalShowProps {
    tenant: TenantInfo;
    branch: BranchInfo;
    packages: PackageCard[];
}

export default function PortalShow({ tenant, branch, packages }: PortalShowProps) {
    const { flash } = usePage<SharedData>().props;
    const checkoutForm = useForm({
        package_id: packages[0]?.id?.toString() ?? '',
        phone_number: '',
    });

    const voucherForm = useForm({
        voucher_code: '',
    });

    useEffect(() => {
        if (!checkoutForm.data.package_id && packages[0]) {
            checkoutForm.setData('package_id', packages[0].id.toString());
        }
    }, [checkoutForm, packages]);

    const submitCheckout = (event: FormEvent) => {
        event.preventDefault();

        checkoutForm.transform((data) => ({
            ...data,
            package_id: Number(data.package_id),
        }));

        checkoutForm.post(route('portal.checkout', { tenantSlug: tenant.slug, branchCode: branch.code }));
    };

    const submitVoucher = (event: FormEvent) => {
        event.preventDefault();
        voucherForm.post(route('portal.voucher.redeem', { tenantSlug: tenant.slug, branchCode: branch.code }));
    };

    const selectedPackage = packages.find((item) => item.id.toString() === checkoutForm.data.package_id) ?? null;

    return (
        <>
            <Head title={`${branch.name} Hotspot`} />

            <div className="bg-background text-foreground relative min-h-screen overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-[28rem] bg-[radial-gradient(circle_at_top_left,rgba(37,99,235,0.18),transparent_36%),radial-gradient(circle_at_top_right,rgba(13,148,136,0.14),transparent_30%)]" />
                <div className="gofi-grid absolute inset-0 opacity-35" />

                <div className="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-5 sm:px-6 lg:px-8">
                    <header className="gofi-shell px-5 py-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="bg-primary text-primary-foreground flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg">
                                    <Wifi className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-lg font-semibold tracking-tight">{tenant.name}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {branch.name} ({branch.code})
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <Badge variant="outline" className="border-primary/20 bg-primary/8 text-primary rounded-full px-3 py-1">
                                    Hotspot access portal
                                </Badge>
                                <Link href={route('home')} className="text-muted-foreground text-sm font-medium hover:text-foreground">
                                    Back to Go-Fi
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main className="mt-6 flex flex-1 flex-col gap-6">
                        {flash?.error && (
                            <Alert variant="destructive">
                                <CircleAlert className="size-4" />
                                <AlertTitle>Payment issue</AlertTitle>
                                <AlertDescription>{flash.error}</AlertDescription>
                            </Alert>
                        )}

                        <section className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                            <div className="gofi-shell px-6 py-7 sm:px-8">
                                <Badge variant="outline" className="border-primary/20 bg-primary/8 text-primary rounded-full px-3 py-1">
                                    Public access flow
                                </Badge>
                                <h1 className="mt-4 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Buy a package or redeem a voucher to get online.
                                </h1>
                                <p className="text-muted-foreground mt-4 max-w-2xl text-sm leading-7 sm:text-base">
                                    This portal is branch-aware, mobile-money ready, and designed for quick customer activation on low-end mobile browsers.
                                </p>

                                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                    <div className="gofi-surface px-4 py-4">
                                        <div className="flex items-start gap-3">
                                            <div className="bg-primary/10 text-primary flex h-10 w-10 items-center justify-center rounded-xl">
                                                <MapPin className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{branch.location || branch.name}</p>
                                                <p className="text-muted-foreground mt-1 text-sm leading-6">{branch.address || 'Branch hotspot is ready for access sales.'}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="gofi-surface px-4 py-4">
                                        <div className="flex items-start gap-3">
                                            <div className="bg-accent text-primary flex h-10 w-10 items-center justify-center rounded-xl">
                                                <Smartphone className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="font-medium">Mobile money first</p>
                                                <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                    Start checkout with a valid mobile number, then approve the payment prompt on the customer device.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Card className="border-border/70 bg-card/88 backdrop-blur">
                                <CardHeader>
                                    <CardTitle>How this hotspot works</CardTitle>
                                    <CardDescription>Use whichever path fits the customer at the point of sale.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {[
                                        ['Choose a package', 'Pick a branch offer with the right time or data shape for this customer.'],
                                        ['Confirm payment', 'Enter the customer number and wait for the mobile-money approval prompt.'],
                                        ['Use voucher fallback', 'If the customer already has a code, redeem it instantly here.'],
                                    ].map(([title, description], index) => (
                                        <div key={title} className="border-border/60 rounded-2xl border px-4 py-4">
                                            <p className="text-primary text-xs font-semibold tracking-[0.22em] uppercase">Step 0{index + 1}</p>
                                            <p className="mt-2 font-medium">{title}</p>
                                            <p className="text-muted-foreground mt-1 text-sm leading-6">{description}</p>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </section>

                        <section className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                            <Card className="border-border/70 bg-card/88 backdrop-blur">
                                <CardHeader>
                                    <CardTitle>Available packages</CardTitle>
                                    <CardDescription>Only active offers for this branch are shown here.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {packages.length === 0 ? (
                                        <Alert variant="destructive">
                                            <CircleAlert className="size-4" />
                                            <AlertTitle>No packages available</AlertTitle>
                                            <AlertDescription>This hotspot does not have any active packages yet. Voucher redemption may still work if a valid code exists.</AlertDescription>
                                        </Alert>
                                    ) : (
                                        packages.map((item) => {
                                            const isSelected = checkoutForm.data.package_id === item.id.toString();

                                            return (
                                                <button
                                                    key={item.id}
                                                    type="button"
                                                    onClick={() => checkoutForm.setData('package_id', item.id.toString())}
                                                    className={`w-full rounded-3xl border px-4 py-4 text-left transition ${
                                                        isSelected
                                                            ? 'border-primary bg-primary/7 ring-primary/20 ring-2'
                                                            : 'border-border/70 bg-background/70 hover:border-primary/40'
                                                    }`}
                                                >
                                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <p className="text-base font-semibold">{item.name}</p>
                                                                {item.is_branch_specific && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        Branch offer
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <p className="text-muted-foreground mt-2 text-sm leading-6">
                                                                {item.description || 'Hotspot access package ready for branch activation.'}
                                                            </p>
                                                        </div>

                                                        <div className="text-left sm:text-right">
                                                            <p className="text-primary text-lg font-semibold">{formatMoney(item.price, item.currency)}</p>
                                                            <p className="text-muted-foreground text-xs uppercase">One-time purchase</p>
                                                        </div>
                                                    </div>

                                                    <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                            <p className="text-muted-foreground text-xs uppercase">Duration</p>
                                                            <p className="mt-2 font-medium">{formatMinutes(item.duration_minutes)}</p>
                                                        </div>
                                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                            <p className="text-muted-foreground text-xs uppercase">Data cap</p>
                                                            <p className="mt-2 font-medium">{formatDataLimit(item.data_limit_mb)}</p>
                                                        </div>
                                                        <div className="bg-muted/45 rounded-xl px-3 py-3">
                                                            <p className="text-muted-foreground text-xs uppercase">Speed</p>
                                                            <p className="mt-2 font-medium">{formatSpeed(item.speed_limit_kbps)}</p>
                                                        </div>
                                                    </div>
                                                </button>
                                            );
                                        })
                                    )}
                                    <InputError message={checkoutForm.errors.package_id} />
                                </CardContent>
                            </Card>

                            <div className="space-y-6">
                                <Card className="border-border/70 bg-card/88 backdrop-blur">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <CreditCard className="size-5" />
                                            Mobile money checkout
                                        </CardTitle>
                                        <CardDescription>Use the customer phone number linked to their mobile-money wallet.</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <form onSubmit={submitCheckout} className="space-y-4">
                                            <div className="rounded-2xl border border-dashed px-4 py-4">
                                                <p className="text-muted-foreground text-xs uppercase">Selected package</p>
                                                <p className="mt-2 font-medium">{selectedPackage?.name || 'Choose a package from the list'}</p>
                                                <p className="text-muted-foreground mt-1 text-sm">
                                                    {selectedPackage ? formatMoney(selectedPackage.price, selectedPackage.currency) : 'No package selected yet'}
                                                </p>
                                            </div>

                                            <div className="space-y-2">
                                                <label htmlFor="phone_number" className="text-sm font-medium">
                                                    Mobile money number
                                                </label>
                                                <Input
                                                    id="phone_number"
                                                    value={checkoutForm.data.phone_number}
                                                    onChange={(event) => checkoutForm.setData('phone_number', event.target.value)}
                                                    placeholder="255712345678"
                                                />
                                                <p className="text-muted-foreground text-xs">
                                                    Use digits only if possible. Country code format helps callback matching later.
                                                </p>
                                                <InputError message={checkoutForm.errors.phone_number} />
                                            </div>

                                            <Button type="submit" className="w-full rounded-xl" disabled={checkoutForm.processing || packages.length === 0}>
                                                Start payment request
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>

                                <Card className="border-border/70 bg-card/88 backdrop-blur">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Ticket className="size-5" />
                                            Redeem voucher
                                        </CardTitle>
                                        <CardDescription>Use this when the customer already has a printed code or scratch card.</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <form onSubmit={submitVoucher} className="space-y-4">
                                            <div className="space-y-2">
                                                <label htmlFor="voucher_code" className="text-sm font-medium">
                                                    Voucher code
                                                </label>
                                                <Input
                                                    id="voucher_code"
                                                    value={voucherForm.data.voucher_code}
                                                    onChange={(event) => voucherForm.setData('voucher_code', event.target.value.toUpperCase())}
                                                    placeholder="CFH-1002"
                                                />
                                                <InputError message={voucherForm.errors.voucher_code} />
                                            </div>

                                            <Button type="submit" variant="outline" className="w-full rounded-xl" disabled={voucherForm.processing}>
                                                Redeem and activate
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}

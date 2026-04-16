import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router, usePage } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface FilterOption {
    label: string;
    value: string;
}

interface FilterField {
    key: string;
    label: string;
    placeholder: string;
    options: FilterOption[];
}

interface OpsFiltersProps {
    search: string;
    searchPlaceholder: string;
    fields?: FilterField[];
    values?: Record<string, string>;
    resultLabel: string;
}

export function OpsFilters({ search, searchPlaceholder, fields = [], values = {}, resultLabel }: OpsFiltersProps) {
    const page = usePage();
    const [form, setForm] = useState<Record<string, string>>({
        search,
        ...values,
    });

    useEffect(() => {
        setForm({
            search,
            ...values,
        });
    }, [search, values]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        router.get(page.url.split('?')[0], Object.fromEntries(Object.entries(form).filter(([, value]) => value && value !== 'all')), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const clear = () => {
        const next = {
            search: '',
            ...Object.fromEntries(fields.map((field) => [field.key, 'all'])),
        };

        setForm(next);

        router.get(page.url.split('?')[0], {}, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <Card className="border-border/70">
            <CardContent className="flex flex-col gap-4 p-4">
                <form onSubmit={submit} className="flex flex-col gap-3 xl:flex-row xl:items-end">
                    <div className="min-w-0 flex-1">
                        <p className="text-muted-foreground mb-2 text-xs font-semibold tracking-[0.18em] uppercase">Search</p>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={form.search ?? ''}
                                onChange={(event) => setForm((current) => ({ ...current, search: event.target.value }))}
                                placeholder={searchPlaceholder}
                                className="pl-9"
                            />
                        </div>
                    </div>

                    {fields.map((field) => (
                        <div key={field.key} className="xl:w-52">
                            <p className="text-muted-foreground mb-2 text-xs font-semibold tracking-[0.18em] uppercase">{field.label}</p>
                            <Select
                                value={form[field.key] ?? 'all'}
                                onValueChange={(value) => setForm((current) => ({ ...current, [field.key]: value }))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={field.placeholder} />
                                </SelectTrigger>
                                <SelectContent>
                                    {field.options.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    ))}

                    <div className="flex gap-2 xl:pb-px">
                        <Button type="submit">Apply</Button>
                        <Button type="button" variant="outline" onClick={clear}>
                            <X className="size-4" />
                            Clear
                        </Button>
                    </div>
                </form>

                <p className="text-muted-foreground text-sm">{resultLabel}</p>
            </CardContent>
        </Card>
    );
}

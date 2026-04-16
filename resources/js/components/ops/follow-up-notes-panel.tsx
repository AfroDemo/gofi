import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/formatters';
import { FormEvent } from 'react';

interface NoteAuthor {
    name: string;
    email: string;
}

export interface FollowUpNoteRow {
    id: number;
    note: string;
    created_at: string | null;
    author: NoteAuthor | null;
}

interface FollowUpNotesPanelProps {
    title: string;
    description: string;
    notes: FollowUpNoteRow[];
    note: string;
    onNoteChange: (value: string) => void;
    onSubmit: (event: FormEvent) => void;
    error?: string;
    processing?: boolean;
    emptyMessage: string;
}

export function FollowUpNotesPanel({
    title,
    description,
    notes,
    note,
    onNoteChange,
    onSubmit,
    error,
    processing = false,
    emptyMessage,
}: FollowUpNotesPanelProps) {
    return (
        <Card className="border-border/70">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="follow_up_note">New note</Label>
                        <textarea
                            id="follow_up_note"
                            value={note}
                            onChange={(event) => onNoteChange(event.target.value)}
                            placeholder="Capture what you checked, what still needs follow-up, or who has picked this up."
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                        />
                        <InputError message={error} />
                    </div>

                    <Button type="submit" disabled={processing} className="rounded-xl">
                        Save follow-up note
                    </Button>
                </form>

                {notes.length === 0 && (
                    <div className="border-border/60 text-muted-foreground rounded-2xl border border-dashed px-4 py-8 text-center text-sm">
                        {emptyMessage}
                    </div>
                )}

                {notes.map((item) => (
                    <div key={item.id} className="border-border/60 rounded-2xl border px-4 py-4">
                        <p className="text-sm leading-6">{item.note}</p>
                        <p className="text-muted-foreground mt-3 text-sm">
                            {formatDateTime(item.created_at)} • {item.author?.name || 'Unknown operator'}
                            {item.author?.email ? ` • ${item.author.email}` : ''}
                        </p>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

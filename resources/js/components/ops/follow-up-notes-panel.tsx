import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/formatters';
import { router } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

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

interface FollowUpOwner {
    assigned_at: string | null;
    status: string;
    resolved_at: string | null;
    acknowledged_at: string | null;
    assigned_user_id?: number | null;
    owned_by_viewer: boolean;
    assigned_user: NoteAuthor | null;
    assigned_by: NoteAuthor | null;
    resolved_by: NoteAuthor | null;
    acknowledged_by: NoteAuthor | null;
}

interface AssignableUser {
    id: number;
    name: string;
    email: string;
}

interface FollowUpNotesPanelProps {
    title: string;
    description: string;
    notes: FollowUpNoteRow[];
    followUp?: FollowUpOwner | null;
    assignHref: string;
    acknowledgeHref: string;
    resolveHref: string;
    reopenHref: string;
    releaseOwnershipHref: string;
    assignableUsers: AssignableUser[];
    preferredAssigneeId?: number | null;
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
    followUp = null,
    assignHref,
    acknowledgeHref,
    resolveHref,
    reopenHref,
    releaseOwnershipHref,
    assignableUsers,
    preferredAssigneeId = null,
    note,
    onNoteChange,
    onSubmit,
    error,
    processing = false,
    emptyMessage,
}: FollowUpNotesPanelProps) {
    const [assignedUserId, setAssignedUserId] = useState<string>(
        String(followUp?.assigned_user_id ?? preferredAssigneeId ?? assignableUsers[0]?.id ?? ''),
    );

    useEffect(() => {
        setAssignedUserId(String(followUp?.assigned_user_id ?? preferredAssigneeId ?? assignableUsers[0]?.id ?? ''));
    }, [followUp?.assigned_user_id, preferredAssigneeId, assignableUsers]);

    const assignSelectedUser = () => {
        if (! assignedUserId) {
            return;
        }

        router.post(
            assignHref,
            { assigned_user_id: assignedUserId },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <Card className="border-border/70">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="border-border/60 rounded-2xl border px-4 py-4">
                    <p className="font-medium">Current owner</p>
                    {followUp ? (
                        <>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">
                                Status: {followUp.status.replaceAll('_', ' ')}
                            </p>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">
                                {followUp.assigned_user?.name || 'Unknown operator'}
                                {followUp.assigned_user?.email ? ` • ${followUp.assigned_user.email}` : ''}
                            </p>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Owned since {formatDateTime(followUp.assigned_at)}
                                {followUp.assigned_by?.name ? ` • assigned by ${followUp.assigned_by.name}` : ''}
                            </p>
                            {followUp.acknowledged_at ? (
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Acknowledged {formatDateTime(followUp.acknowledged_at)}
                                    {followUp.acknowledged_by?.name ? ` • by ${followUp.acknowledged_by.name}` : ''}
                                </p>
                            ) : (
                                <p className="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                    This follow-up is still waiting for acknowledgement from the assigned operator.
                                </p>
                            )}
                            {followUp.status === 'resolved' && (
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Resolved {formatDateTime(followUp.resolved_at)}
                                    {followUp.resolved_by?.name ? ` • by ${followUp.resolved_by.name}` : ''}
                                </p>
                            )}
                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                {assignableUsers.length > 0 && (
                                    <div className="flex-1 space-y-2">
                                        <Label htmlFor="assigned_user_id">Assign to</Label>
                                        <select
                                            id="assigned_user_id"
                                            value={assignedUserId}
                                            onChange={(event) => setAssignedUserId(event.target.value)}
                                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                        >
                                            {assignableUsers.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name} {user.email ? `(${user.email})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                                <Button type="button" variant="outline" className="rounded-xl" onClick={assignSelectedUser}>
                                    {followUp.owned_by_viewer ? 'Reassign follow-up' : 'Assign follow-up'}
                                </Button>
                                {!followUp.acknowledged_at && followUp.owned_by_viewer && followUp.status !== 'resolved' && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-xl"
                                        onClick={() =>
                                            router.post(acknowledgeHref, {}, {
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        Acknowledge follow-up
                                    </Button>
                                )}
                                {followUp.status === 'resolved' ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-xl"
                                        onClick={() =>
                                            router.post(reopenHref, {}, {
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        Reopen follow-up
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="rounded-xl"
                                        onClick={() =>
                                            router.post(resolveHref, {}, {
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        Mark resolved
                                    </Button>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-xl"
                                    onClick={() =>
                                        router.delete(releaseOwnershipHref, {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    Release ownership
                                </Button>
                            </div>
                        </>
                    ) : (
                        <>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">
                                No operator currently owns this follow-up. Take ownership to signal that you are driving the investigation.
                            </p>
                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                {assignableUsers.length > 0 && (
                                    <div className="flex-1 space-y-2">
                                        <Label htmlFor="assigned_user_id_empty">Assign to</Label>
                                        <select
                                            id="assigned_user_id_empty"
                                            value={assignedUserId}
                                            onChange={(event) => setAssignedUserId(event.target.value)}
                                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                                        >
                                            {assignableUsers.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name} {user.email ? `(${user.email})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                                <Button type="button" variant="outline" className="rounded-xl" onClick={assignSelectedUser}>
                                    Assign follow-up
                                </Button>
                            </div>
                        </>
                    )}
                </div>

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

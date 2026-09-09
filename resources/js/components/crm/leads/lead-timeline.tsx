import { router } from '@inertiajs/react';
import {
  Check,
  CircleDot,
  FileText,
  MapPin,
  Mail,
  MessageCircle,
  Pencil,
  Phone,
  Plus,
  RotateCcw,
  StickyNote,
  Trash2,
  Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { LeadActivityFormModal } from '@/components/crm/leads/lead-activity-form-modal';
import { RowActions } from '@/components/crm/row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { daysFromToday, formatDateTime, relativeTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { destroy, toggle } from '@/routes/activities';
import type { LeadActivity, Option } from '@/types/crm';

const ICONS: Record<string, LucideIcon> = {
  note: StickyNote,
  call: Phone,
  meeting: Users,
  email: Mail,
  whatsapp: MessageCircle,
  visit: MapPin,
  proposal: FileText,
  follow_up: RotateCcw,
  other: CircleDot,
};

type Marker = { label: string; className: string } | null;

function marker(activity: LeadActivity): Marker {
  if (activity.completed_at !== null) {
    return null;
  }

  const days = daysFromToday(activity.scheduled_at);

  if (days === null) {
    return { label: 'Belum dikerjakan', className: 'text-amber-700 dark:text-amber-300' };
  }

  if (days < 0) {
    return {
      label: `Telat ${Math.abs(days)} hari`,
      className: 'border-red-200 text-red-700 dark:border-red-900 dark:text-red-300',
    };
  }

  return {
    label: days === 0 ? 'Dijadwalkan hari ini' : `Dijadwalkan ${days} hari lagi`,
    className: 'border-amber-200 text-amber-700 dark:border-amber-900 dark:text-amber-300',
  };
}

export function LeadTimeline({
  leadId,
  timeline,
  activityTypes,
}: {
  leadId: number;
  timeline: LeadActivity[];
  activityTypes: Option[];
}) {
  const [confirm, confirmDialog] = useConfirm();
  const [modal, setModal] = useState<{ activity?: LeadActivity } | null>(null);

  async function remove(activity: LeadActivity) {
    const confirmed = await confirm({
      title: `Hapus catatan "${activity.title}"?`,
      description: 'Isi catatan ini hilang permanen dari timeline.',
      confirmLabel: 'Hapus catatan',
      destructive: true,
    });

    if (confirmed) {
      router.delete(destroy(activity.id), { preserveScroll: true });
    }
  }

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-3 space-y-0">
        <div className="grid gap-0.5">
          <CardTitle className="text-base">Catatan &amp; aktivitas</CardTitle>
          <p className="text-xs text-muted-foreground">
            Riwayat follow up, meeting, telepon, dan catatan lain untuk lead ini.
          </p>
        </div>

        <Button type="button" variant="outline" size="sm" onClick={() => setModal({})}>
          <Plus className="size-3.5" />
          Catatan
        </Button>
      </CardHeader>

      <CardContent>
        {timeline.length === 0 && (
          <p className="rounded-lg border border-dashed px-3 py-10 text-center text-sm text-muted-foreground">
            Belum ada catatan. Mulai dari hasil kontak pertama dengan klien ini.
          </p>
        )}

        <ol className="space-y-0">
          {timeline.map((activity) => {
            const Icon = ICONS[activity.type] ?? CircleDot;
            const flag = marker(activity);
            const done = activity.completed_at !== null;

            return (
              <li key={activity.id} className="group/item relative flex gap-3 pb-5 last:pb-0">
                <span
                  aria-hidden
                  className="absolute top-9 bottom-0 left-4 w-px -translate-x-1/2 bg-border group-last/item:hidden"
                />

                <span
                  aria-hidden
                  className={cn(
                    'grid size-8 shrink-0 place-items-center rounded-full border bg-card',
                    done ? 'text-muted-foreground' : 'border-primary/40 text-primary',
                  )}
                >
                  <Icon className="size-4" />
                </span>

                <div className="min-w-0 flex-1 rounded-lg border p-3">
                  <div className="flex items-start gap-2">
                    <p className="min-w-0 flex-1 text-sm font-medium break-words">
                      {activity.title}
                    </p>

                    <RowActions
                      label={activity.title}
                      actions={[
                        {
                          label: 'Ubah catatan',
                          icon: Pencil,
                          onSelect: () => setModal({ activity }),
                        },
                        {
                          label: done ? 'Tandai belum dikerjakan' : 'Tandai sudah terjadi',
                          icon: done ? RotateCcw : Check,
                          onSelect: () =>
                            router.post(toggle(activity.id), {}, { preserveScroll: true }),
                        },
                        {
                          label: 'Hapus catatan',
                          icon: Trash2,
                          destructive: true,
                          onSelect: () => remove(activity),
                        },
                      ]}
                    />
                  </div>

                  <div className="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                    <Badge variant="secondary" className="border-transparent px-1.5 py-0">
                      {activity.type_label}
                    </Badge>

                    <span>{formatDateTime(activity.scheduled_at ?? activity.created_at)}</span>

                    {activity.user && <span>· {activity.user.name}</span>}

                    {flag && (
                      <span className={cn('rounded-md border px-1.5 py-0.5', flag.className)}>
                        {flag.label}
                      </span>
                    )}
                  </div>

                  {activity.description && (
                    <p className="mt-2 text-sm whitespace-pre-line text-muted-foreground">
                      {activity.description}
                    </p>
                  )}

                  {activity.created_at && activity.scheduled_at && (
                    <p className="mt-2 text-[11px] text-muted-foreground/70">
                      Dicatat {relativeTime(activity.created_at)}
                    </p>
                  )}
                </div>
              </li>
            );
          })}
        </ol>
      </CardContent>

      {modal && (
        <LeadActivityFormModal
          leadId={leadId}
          activity={modal.activity}
          activityTypes={activityTypes}
          onClose={() => setModal(null)}
        />
      )}

      {confirmDialog}
    </Card>
  );
}

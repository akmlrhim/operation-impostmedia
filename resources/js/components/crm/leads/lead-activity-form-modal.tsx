import { useForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DateTimeField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/activities';
import type { LeadActivity, Option } from '@/types/crm';

type ActivityFormData = {
  type: string;
  title: string;
  description: string;
  scheduled_at: string;
  completed: boolean;
};

function localNow() {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60_000;

  return new Date(now.getTime() - offset).toISOString().slice(0, 16);
}

export function LeadActivityFormModal({
  leadId,
  activity,
  activityTypes,
  onClose,
}: {
  leadId: number;
  activity?: LeadActivity;
  activityTypes: Option[];
  onClose: () => void;
}) {
  const isEdit = Boolean(activity);

  const form = useForm<ActivityFormData>({
    type: activity?.type ?? 'follow_up',
    title: activity?.title ?? '',
    description: activity?.description ?? '',
    scheduled_at: activity?.scheduled_at ? activity.scheduled_at.slice(0, 16) : localNow(),
    completed: activity ? activity.completed_at !== null : true,
  });

  return (
    <FormModal
      title={isEdit ? 'Ubah catatan' : 'Catatan baru'}
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && activity ? update(activity.id) : store(leadId), {
          preserveScroll: true,
          onSuccess: onClose,
        });
      }}
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" disabled={form.processing}>
            {isEdit ? 'Simpan perubahan' : 'Tambah catatan'}
          </Button>
        </>
      }
    >
      <FormGrid>
        <Field label="Jenis" error={form.errors.type}>
          <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {activityTypes.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </Field>

        <Field label="Waktu" htmlFor="scheduled_at" error={form.errors.scheduled_at}>
          <DateTimeField
            id="scheduled_at"
            value={form.data.scheduled_at}
            onChange={(value) => form.setData('scheduled_at', value)}
          />
        </Field>

        <Field
          label="Judul"
          htmlFor="activity_title"
          required
          className="sm:col-span-2"
          error={form.errors.title}
        >
          <Input
            id="activity_title"
            value={form.data.title}
            onChange={(e) => form.setData('title', e.target.value)}
            placeholder="Misal: Meeting di kantor klien"
            required
          />
        </Field>

        <Field
          label="Detail"
          htmlFor="activity_description"
          className="sm:col-span-2"
          error={form.errors.description}
        >
          <Textarea
            id="activity_description"
            rows={7}
            value={form.data.description}
            onChange={(e) => form.setData('description', e.target.value)}
            placeholder={
              'Siapa yang hadir, apa yang dibahas, hasil, dan tindak lanjutnya.\n\nBoleh ditulis panjang dan pakai baris baru.'
            }
          />
        </Field>

        <div className="flex items-center gap-2 sm:col-span-2">
          <Checkbox
            id="activity_completed"
            checked={form.data.completed}
            onCheckedChange={(checked) => form.setData('completed', checked === true)}
          />
          <Label htmlFor="activity_completed" className="text-sm font-normal">
            Sudah terjadi
          </Label>
          <span className="text-xs text-muted-foreground">
            Lepas centang kalau ini rencana yang belum dikerjakan.
          </span>
        </div>
      </FormGrid>
    </FormModal>
  );
}

import { useForm } from '@inertiajs/react';
import { FormModal } from '@/components/crm/form-modal';
import { LeadFormModalFields } from '@/components/crm/lead-form-modal-fields';
import type { LeadFormData } from '@/components/crm/lead-form-modal-types';
import { Button } from '@/components/ui/button';
import { store, update } from '@/routes/leads';
import type { LeadCard, Option, UserRef } from '@/types/crm';

type Props = {
  lead?: LeadCard;
  stageId: number | null;
  stages: { id: number; name: string }[];
  users: UserRef[];
  priorities: Option[];
  sources: Option[];
  statuses: Option[];
  onClose: () => void;
};

export function LeadFormModal({
  lead,
  stageId,
  stages,
  users,
  priorities,
  sources,
  statuses,
  onClose,
}: Props) {
  const isEdit = Boolean(lead);

  const form = useForm<LeadFormData>({
    lead_stage_id: String(stageId ?? stages[0]?.id ?? ''),
    company_name: lead?.company_name ?? '',
    contact_name: lead?.contact_name ?? '',
    email: lead?.email ?? '',
    phone: lead?.phone ?? '',
    source: lead?.source ?? '',
    estimated_value: lead ? String(lead.estimated_value) : '0',
    expected_close_date: lead?.expected_close_date ?? '',
    priority: lead?.priority ?? 'medium',
    owner_id: lead?.owner_id ? String(lead.owner_id) : '',
    status: lead?.status ?? 'open',
    lost_reason: lead?.lost_reason ?? '',
    next_follow_up_at: lead?.next_follow_up_at ? lead.next_follow_up_at.slice(0, 16) : '',
    notes: lead?.notes ?? '',
  });

  const title = isEdit ? `Ubah ${lead?.company_name}` : 'Lead baru';

  return (
    <FormModal
      title={title}
      size="lg"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && lead ? update(lead.id) : store(), {
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
            {isEdit ? 'Simpan perubahan' : 'Tambah lead'}
          </Button>
        </>
      }
    >
      <LeadFormModalFields
        form={form}
        stages={stages}
        users={users}
        priorities={priorities}
        sources={sources}
        statuses={statuses}
      />
    </FormModal>
  );
}

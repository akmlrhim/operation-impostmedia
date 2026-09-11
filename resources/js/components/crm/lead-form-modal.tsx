import { useForm } from '@inertiajs/react';
import { FormModal } from '@/components/crm/form-modal';
import { LeadFormModalFields } from '@/components/crm/lead-form-modal-fields';
import type { LeadFormData } from '@/components/crm/lead-form-modal-types';
import { Button } from '@/components/ui/button';
import { store, update } from '@/routes/leads';
import type { LeadCard, Option, ServiceOption, UserOption } from '@/types/crm';

type Props = {
  lead?: LeadCard;
  stageId: number | null;
  stages: { id: number; name: string }[];
  sources: Option[];
  statuses: Option[];
  temperatures: Option[];
  services: ServiceOption[];
  users: UserOption[];
  onClose: () => void;
};

function today() {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60_000;

  return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

export function LeadFormModal({
  lead,
  stageId,
  stages,
  sources,
  statuses,
  temperatures,
  services,
  users,
  onClose,
}: Props) {
  const isEdit = Boolean(lead);

  const form = useForm<LeadFormData>({
    lead_stage_id: String(stageId ?? stages[0]?.id ?? ''),
    date_in: lead?.date_in ?? today(),
    company_name: lead?.company_name ?? '',
    industry: lead?.industry ?? '',
    vacancy_position: lead?.vacancy_position ?? '',
    contact_name: lead?.contact_name ?? '',
    email: lead?.email ?? '',
    phone: lead?.phone ?? '',
    region: lead?.region ?? '',
    source: lead?.source ?? '',
    pic: lead?.pic ?? '',
    assigned_to_ids: lead?.assignees?.map((user) => user.id) ?? [],
    service_package_ids: lead?.service_packages?.map((servicePackage) => servicePackage.id) ?? [],
    estimated_value: lead ? String(lead.estimated_value) : '0',
    last_contact_date: lead?.last_contact_date ?? '',
    next_action_date: lead?.next_action_date ?? '',
    next_action: lead?.next_action ?? '',
    meeting_date: lead?.meeting_date ?? '',
    temperature: lead?.temperature ?? 'cold',
    notes: lead?.notes ?? '',
    folder_url: lead?.folder_url ?? '',
    status: lead?.status ?? 'open',
    lost_reason: lead?.lost_reason ?? '',
  });

  const title = isEdit ? `Ubah ${lead?.company_name}` : 'Lead baru';

  return (
    <FormModal
      title={title}
      size="xl"
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
        lead={lead}
        stages={stages}
        sources={sources}
        statuses={statuses}
        temperatures={temperatures}
        services={services}
        users={users}
      />
    </FormModal>
  );
}

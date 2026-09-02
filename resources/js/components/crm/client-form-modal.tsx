import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { ClientFormModalCompany } from '@/components/crm/client-form-modal-company';
import { ClientFormModalContact } from '@/components/crm/client-form-modal-contact';
import type { ClientFormData } from '@/components/crm/client-form-modal-types';
import { FormModal } from '@/components/crm/form-modal';
import { FormSection } from '@/components/crm/form-section';
import { Button } from '@/components/ui/button';
import { shortCode as shortCodeUrl, store, update } from '@/routes/clients';
import type { Client, Option, UserRef } from '@/types/crm';

type Props = {
  client?: Client;
  statuses: Option[];
  users: UserRef[];
  onClose: () => void;
};

export function ClientFormModal({ client, statuses, users, onClose }: Props) {
  const isEdit = Boolean(client);

  const form = useForm<ClientFormData>({
    company_name: client?.company_name ?? '',
    short_code: client?.short_code ?? '',
    email: client?.email ?? '',
    phone: client?.phone ?? '',
    address: client?.address ?? '',
    city: client?.city ?? '',
    contact_name: client?.contact_name ?? '',
    contact_position: client?.contact_position ?? '',
    contact_email: client?.contact_email ?? '',
    contact_phone: client?.contact_phone ?? '',
    status: client?.status ?? 'active',
    account_manager_id: client?.account_manager_id ? String(client.account_manager_id) : '',
    notes: client?.notes ?? '',
  });

  const title = isEdit ? `Ubah ${client?.company_name}` : 'Klien baru';

  const shortCodeEdited = useRef(false);
  const shortCodeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  function changeCompanyName(value: string) {
    form.setData('company_name', value);

    if (isEdit || shortCodeEdited.current) {
      return;
    }

    if (shortCodeTimer.current) {
      clearTimeout(shortCodeTimer.current);
    }

    const companyName = value.trim();

    if (companyName === '') {
      form.setData('short_code', '');

      return;
    }

    shortCodeTimer.current = setTimeout(() => {
      fetch(shortCodeUrl({ query: { company_name: companyName } }).url, {
        headers: { Accept: 'application/json' },
      })
        .then((response) => (response.ok ? response.json() : null))
        .then((payload: { short_code?: string } | null) => {
          if (payload?.short_code && !shortCodeEdited.current) {
            form.setData('short_code', payload.short_code);
          }
        })
        .catch(() => undefined);
    }, 300);
  }

  return (
    <FormModal
      title={title}
      size="lg"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && client ? update(client.id) : store(), {
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
            {isEdit ? 'Simpan perubahan' : 'Simpan klien'}
          </Button>
        </>
      }
    >
      <div className="grid gap-6 lg:grid-cols-2">
        <FormSection title="Profil perusahaan">
          <ClientFormModalCompany
            form={form}
            isEdit={isEdit}
            statuses={statuses}
            onChangeCompanyName={changeCompanyName}
            onShortCodeEdited={() => {
              shortCodeEdited.current = true;
            }}
          />
        </FormSection>

        <FormSection title="PIC & penanggung jawab" className="lg:border-l lg:pl-6">
          <ClientFormModalContact form={form} users={users} />
        </FormSection>
      </div>
    </FormModal>
  );
}

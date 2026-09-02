import { Head, useForm } from '@inertiajs/react';
import { CompanySettingsBilling } from '@/components/settings/company-settings-billing';
import { CompanySettingsBranding } from '@/components/settings/company-settings-branding';
import { CompanySettingsProfile } from '@/components/settings/company-settings-profile';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Button } from '@/components/ui/button';
import { edit, update } from '@/routes/company';
import type { CompanyProfile } from '@/types/crm';

type Props = {
  company: CompanyProfile;
  logoUrl: string | null;
  signatureUrl: string | null;
  stampUrl: string | null;
};

export default function CompanySettings({ company, logoUrl, signatureUrl, stampUrl }: Props) {
  const form = useForm<CompanyFormData>({
    name: company.name,
    email: company.email ?? '',
    phone: company.phone ?? '',
    address: company.address ?? '',
    city: company.city ?? '',
    signatory_name: company.signatory_name ?? '',
    signatory_position: company.signatory_position ?? '',
    invoice_notes: company.invoice_notes ?? '',
    terms: company.terms ?? '',
    logo: null,
    signature: null,
    stamp: null,
    remove_logo: false,
    remove_signature: false,
    remove_stamp: false,
  });

  return (
    <>
      <Head title="Profil perusahaan" />

      <form
        className="space-y-6"
        onSubmit={(e) => {
          e.preventDefault();

          form.transform((data) => {
            const payload: Record<string, unknown> = { ...data, _method: 'put' };

            if (data.logo === null) {
              delete payload.logo;
            }

            if (data.signature === null) {
              delete payload.signature;
            }

            if (data.stamp === null) {
              delete payload.stamp;
            }

            return payload;
          });

          form.post(update().url, { preserveScroll: true, forceFormData: true });
        }}
      >
        <CompanySettingsProfile form={form} />
        <CompanySettingsBranding
          form={form}
          logoUrl={logoUrl}
          signatureUrl={signatureUrl}
          stampUrl={stampUrl}
        />
        <CompanySettingsBilling form={form} />

        <Button type="submit" disabled={form.processing}>
          Simpan
        </Button>
      </form>
    </>
  );
}

CompanySettings.layout = {
  breadcrumbs: [{ title: 'Profil perusahaan', href: edit() }],
};

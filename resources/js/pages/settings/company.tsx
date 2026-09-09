import { Head, useForm } from '@inertiajs/react';
import { CompanySettingsBranding } from '@/components/settings/company-settings-branding';
import { CompanySettingsProfile } from '@/components/settings/company-settings-profile';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Button } from '@/components/ui/button';
import { edit, update } from '@/routes/company';
import type { CompanyIdentity } from '@/types/crm';

type Props = {
  profile: CompanyIdentity;
  logoUrl: string | null;
  signatureUrl: string | null;
  stampUrl: string | null;
};

export default function CompanySettingsPage({
  profile,
  logoUrl,
  signatureUrl,
  stampUrl,
}: Props) {
  const form = useForm<CompanyFormData>({
    signature: null,
    stamp: null,
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
        <CompanySettingsProfile profile={profile} logoUrl={logoUrl} />
        <CompanySettingsBranding
          form={form}
          signatureUrl={signatureUrl}
          stampUrl={stampUrl}
        />

        <Button type="submit" disabled={form.processing}>
          Simpan
        </Button>
      </form>
    </>
  );
}

CompanySettingsPage.layout = {
  breadcrumbs: [{ title: 'Profil perusahaan', href: edit() }],
};

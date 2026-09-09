import Heading from '@/components/heading';
import type { CompanyIdentity } from '@/types/crm';

export function CompanySettingsProfile({
  profile,
  logoUrl,
}: {
  profile: CompanyIdentity;
  logoUrl: string | null;
}) {
  const rows = [
    { label: 'Nama perusahaan', value: profile.name },
    { label: 'Alamat', value: profile.address },
    { label: 'Kota', value: profile.city },
    { label: 'Telepon', value: profile.phone },
    { label: 'Email', value: profile.email },
    { label: 'Penanda tangan', value: profile.signatory_name },
    { label: 'Jabatan penanda tangan', value: profile.signatory_position },
  ];

  return (
    <>
      <Heading
        variant="small"
        title="Identitas perusahaan"
        description="Kop surat MoU dan invoice, sekaligus PIHAK KEDUA di setiap MoU"
      />

      <div className="space-y-4 rounded-lg border p-4">
        {logoUrl !== null && (
          <img
            src={logoUrl}
            alt="Logo perusahaan"
            className="size-20 rounded-md border bg-white object-contain p-1"
          />
        )}

        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          {rows.map((row) => (
            <div key={row.label} className="space-y-0.5">
              <dt className="text-xs text-muted-foreground">{row.label}</dt>
              <dd className="font-medium">{row.value}</dd>
            </div>
          ))}
        </dl>
      </div>
    </>
  );
}

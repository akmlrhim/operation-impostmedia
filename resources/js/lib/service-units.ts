export const SERVICE_UNIT_OPTIONS = [
  { value: 'bulan', label: 'bulan' },
  { value: 'minggu', label: 'minggu' },
  { value: 'hari', label: 'hari' },
  { value: 'sesi', label: 'sesi' },
  { value: 'paket', label: 'paket' },
  { value: 'konten', label: 'konten' },
  { value: 'kegiatan', label: 'kegiatan' },
];

export function unitOptions(extraUnit?: string | null): { value: string; label: string }[] {
  const known = new Set(SERVICE_UNIT_OPTIONS.map((option) => option.value));
  const extras = extraUnit && !known.has(extraUnit) ? [{ value: extraUnit, label: extraUnit }] : [];

  return [...extras, ...SERVICE_UNIT_OPTIONS];
}

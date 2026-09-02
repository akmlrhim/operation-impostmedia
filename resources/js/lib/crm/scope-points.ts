import { jsonPostHeaders } from '@/lib/csrf';
import { scopePoints } from '@/routes/contracts';

export async function writeScopePoints(payload: {
  name: string;
  service_package_id: number | null;
  client_id: number | null;
  title: string | null;
}): Promise<string[]> {
  const response = await fetch(scopePoints().url, {
    method: 'POST',
    headers: jsonPostHeaders(),
    body: JSON.stringify(payload),
  });

  const data = (await response.json().catch(() => null)) as {
    points?: string[];
    message?: string;
  } | null;

  if (!response.ok) {
    throw new Error(data?.message ?? `Rincian gagal ditulis (${response.status}).`);
  }

  return data?.points ?? [];
}

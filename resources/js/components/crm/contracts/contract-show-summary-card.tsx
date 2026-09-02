import { Link } from '@inertiajs/react';
import { DetailRow } from '@/components/crm/detail-row';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import type { Contract, Option } from '@/types/crm';

export function ContractShowSummaryCard({
  contract,
  types,
  statuses,
  billingCycles,
}: {
  contract: Contract;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Ringkasan</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3 text-sm">
        <DetailRow label="Status">
          <StatusBadge value={contract.status} options={statuses} />
        </DetailRow>
        <DetailRow label="Jenis">
          {types.find((t) => t.value === contract.type)?.label ?? contract.type}
        </DetailRow>
        <DetailRow label="Klien">
          <Link href={showClient(contract.client_id)} className="hover:underline">
            {contract.client?.company_name}
          </Link>
        </DetailRow>
        <DetailRow label="Periode">
          {formatDate(contract.start_date)} – {formatDate(contract.end_date)}
        </DetailRow>
        <DetailRow label="Ditandatangani">{formatDate(contract.signed_date)}</DetailRow>
        <DetailRow label="Tempat">{contract.signing_place ?? '-'}</DetailRow>
        <DetailRow label="Siklus tagihan">
          {billingCycles.find((c) => c.value === contract.billing_cycle)?.label ??
            contract.billing_cycle}
        </DetailRow>
        <DetailRow label="Invoice berikutnya">{formatDate(contract.next_invoice_date)}</DetailRow>
        <DetailRow label="Pihak Pertama">
          {contract.first_party_name ?? '-'}
          {contract.first_party_position && (
            <span className="block text-xs text-muted-foreground">
              {contract.first_party_position}
            </span>
          )}
        </DetailRow>
        <DetailRow label="Pihak Kedua">
          {contract.second_party_name ?? contract.client?.contact_name ?? '-'}
          {(contract.second_party_position ?? contract.client?.contact_position) && (
            <span className="block text-xs text-muted-foreground">
              {contract.second_party_position ?? contract.client?.contact_position}
            </span>
          )}
        </DetailRow>
      </CardContent>
    </Card>
  );
}

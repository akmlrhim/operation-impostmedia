import { Link } from '@inertiajs/react';
import { DetailRow } from '@/components/crm/detail-row';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import { show as showContract } from '@/routes/contracts';
import type { Invoice, Option } from '@/types/crm';

export function InvoiceShowSummaryCard({
  invoice,
  statuses,
}: {
  invoice: Invoice;
  statuses: Option[];
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Ringkasan</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3 text-sm">
        <DetailRow label="Status">
          <StatusBadge value={invoice.status} options={statuses} />
        </DetailRow>
        <DetailRow label="Klien">
          <Link href={showClient(invoice.client_id)} className="hover:underline">
            {invoice.client?.company_name}
          </Link>
        </DetailRow>
        {invoice.contract && (
          <DetailRow label="MoU">
            <Link href={showContract(invoice.contract.id)} className="hover:underline">
              {invoice.contract.number}
            </Link>
          </DetailRow>
        )}
        <DetailRow label="Terbit">{formatDate(invoice.issue_date)}</DetailRow>
        <DetailRow label="Jatuh tempo">{formatDate(invoice.due_date)}</DetailRow>
        {invoice.period_start && (
          <DetailRow label="Periode">
            {formatDate(invoice.period_start)} – {formatDate(invoice.period_end)}
          </DetailRow>
        )}
      </CardContent>
    </Card>
  );
}

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
          {invoice.client_id === null ? (
            <span className="text-muted-foreground">Tanpa klien</span>
          ) : (
            <Link href={showClient(invoice.client_id)} className="hover:underline">
              {invoice.client?.company_name}
            </Link>
          )}
        </DetailRow>
        {invoice.contract && (
          <DetailRow label="MoU">
            <Link href={showContract(invoice.contract.id)} className="hover:underline">
              {invoice.contract.number}
            </Link>
          </DetailRow>
        )}
        <DetailRow label="Penanggung jawab">
          {invoice.assignees && invoice.assignees.length > 0 ? (
            <ul className="flex flex-wrap gap-1.5">
              {invoice.assignees.map((user) => (
                <li key={user.id}>
                  <span className="inline-flex items-center rounded-md border px-2 py-0.5 text-xs">
                    {user.name}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <span className="text-muted-foreground">Belum ditentukan</span>
          )}
        </DetailRow>
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

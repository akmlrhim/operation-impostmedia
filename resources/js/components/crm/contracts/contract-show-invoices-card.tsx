import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, rupiah } from '@/lib/format';
import { show as showInvoice } from '@/routes/invoices';
import type { Contract } from '@/types/crm';

export function ContractShowInvoicesCard({
  contract,
  canInvoice,
}: {
  contract: Contract;
  canInvoice: boolean;
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Invoice terkait</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {(contract.invoices?.length ?? 0) === 0 && (
          <p className="text-sm text-muted-foreground">
            {canInvoice
              ? 'Belum ada invoice diterbitkan.'
              : 'MoU harus ditandatangani dulu sebelum bisa ditagihkan.'}
          </p>
        )}

        {contract.invoices?.map((invoice) => (
          <Link
            key={invoice.id}
            href={showInvoice(invoice.id)}
            className="flex items-center justify-between gap-3 rounded-lg border p-2.5 text-sm transition-colors hover:bg-accent"
          >
            <div className="min-w-0">
              <p className="truncate text-xs font-medium">{invoice.number}</p>
              <p className="text-xs text-muted-foreground">{formatDate(invoice.issue_date)}</p>
            </div>
            <div className="shrink-0 space-y-1 text-right">
              <p className="font-medium">{rupiah(invoice.total)}</p>
              <StatusBadge value={invoice.status} />
            </div>
          </Link>
        ))}
      </CardContent>
    </Card>
  );
}

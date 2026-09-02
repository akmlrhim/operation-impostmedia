import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, rupiah } from '@/lib/format';
import { show as showContract } from '@/routes/contracts';
import { show as showInvoice } from '@/routes/invoices';
import type { Contract, Invoice } from '@/types/crm';

export function ClientContractsCard({ contracts }: { contracts: Contract[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">MoU &amp; kontrak</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {contracts.length === 0 && (
          <p className="text-sm text-muted-foreground">Belum ada kontrak.</p>
        )}

        {contracts.map((contract) => (
          <Link
            key={contract.id}
            href={showContract(contract.id)}
            className="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm transition-colors hover:bg-accent"
          >
            <div className="min-w-0">
              <p className="truncate font-medium">{contract.title}</p>
              <p className="truncate text-xs text-muted-foreground">{contract.number}</p>
            </div>
            <div className="shrink-0 space-y-1 text-right">
              <p className="font-medium">{rupiah(contract.value)}</p>
              <StatusBadge value={contract.status} />
            </div>
          </Link>
        ))}
      </CardContent>
    </Card>
  );
}

export function ClientInvoicesCard({ invoices }: { invoices: Invoice[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Invoice</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {invoices.length === 0 && (
          <p className="text-sm text-muted-foreground">Belum ada invoice.</p>
        )}

        {invoices.map((invoice) => (
          <Link
            key={invoice.id}
            href={showInvoice(invoice.id)}
            className="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm transition-colors hover:bg-accent"
          >
            <div className="min-w-0">
              <p className="truncate font-medium">{invoice.number}</p>
              <p className="truncate text-xs text-muted-foreground">
                Jatuh tempo {formatDate(invoice.due_date)}
              </p>
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

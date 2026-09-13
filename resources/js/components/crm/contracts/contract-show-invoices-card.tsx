import { Link, router } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, rupiah } from '@/lib/format';
import { useCan } from '@/lib/use-can';
import { invoice as createInvoice } from '@/routes/contracts';
import { show as showInvoice } from '@/routes/invoices';
import type { Contract } from '@/types/crm';

export function ContractShowInvoicesCard({
  contract,
  invoiceBlocker,
}: {
  contract: Contract;
  invoiceBlocker: string | null;
}) {
  const invoices = contract.invoices ?? [];
  const can = useCan();

  return (
    <Card>
      <CardHeader className="flex-row flex-wrap items-center gap-3">
        <CardTitle className="mr-auto text-base">Invoice terkait</CardTitle>

        {can['approve-documents'] && invoiceBlocker === null && (
          <Button
            size="sm"
            variant="outline"
            onClick={() => router.post(createInvoice(contract.id), {}, { preserveScroll: true })}
          >
            <Receipt className="size-4" />
            Buat invoice
          </Button>
        )}
      </CardHeader>

      <CardContent className="space-y-2">
        {invoices.length === 0 && (
          <p className="text-sm text-muted-foreground">Belum ada invoice untuk MoU ini.</p>
        )}

        {invoices.map((invoice) => (
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

        {invoiceBlocker !== null && (
          <p className="text-xs text-muted-foreground">{invoiceBlocker}</p>
        )}
      </CardContent>
    </Card>
  );
}

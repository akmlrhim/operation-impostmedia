import { DetailRow } from '@/components/crm/detail-row';
import { DocumentItems } from '@/components/crm/document-items';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { decimal, rupiah } from '@/lib/format';
import type { Invoice } from '@/types/crm';

export function InvoiceShowItemsCard({ invoice }: { invoice: Invoice }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Item tagihan</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <DocumentItems items={invoice.items ?? []} />

        <dl className="ml-auto w-full max-w-xs space-y-1.5 text-sm">
          <DetailRow label="DPP">{rupiah(invoice.subtotal)}</DetailRow>
          {Number(invoice.discount_amount) > 0 && (
            <DetailRow label="Diskon">−{rupiah(invoice.discount_amount)}</DetailRow>
          )}
          {Number(invoice.tax_amount) > 0 && (
            <DetailRow label={`PPN ${decimal(invoice.tax_percent)}%`}>
              {rupiah(invoice.tax_amount)}
            </DetailRow>
          )}
          <DetailRow label="Total tagihan" strong>
            {rupiah(invoice.total)}
          </DetailRow>
          {Number(invoice.amount_paid) > 0 && (
            <DetailRow label="Sudah dibayar">−{rupiah(invoice.amount_paid)}</DetailRow>
          )}
          <DetailRow label="Sisa tagihan" strong>
            {rupiah(invoice.balance_due)}
          </DetailRow>
        </dl>
      </CardContent>
    </Card>
  );
}

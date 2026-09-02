import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { ConfirmFn } from '@/components/crm/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, rupiah } from '@/lib/format';
import { destroy as destroyPayment } from '@/routes/payments';
import type { Invoice, Option } from '@/types/crm';

export function InvoiceShowPaymentsCard({
  invoice,
  methods,
  confirm,
}: {
  invoice: Invoice;
  methods: Option[];
  confirm: ConfirmFn;
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Riwayat pembayaran</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {(invoice.payments?.length ?? 0) === 0 && (
          <p className="text-sm text-muted-foreground">Belum ada pembayaran tercatat.</p>
        )}

        {invoice.payments?.map((item) => (
          <div
            key={item.id}
            className="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm"
          >
            <div className="min-w-0">
              <p className="font-medium">{rupiah(item.amount)}</p>
              <p className="truncate text-xs text-muted-foreground">
                {formatDate(item.paid_at)} ·{' '}
                {methods.find((m) => m.value === item.method)?.label ?? item.method}
              </p>
            </div>
            <Button
              variant="ghost"
              size="icon"
              aria-label="Hapus pembayaran"
              onClick={async () => {
                const confirmed = await confirm({
                  title: 'Hapus catatan pembayaran ini?',
                  description: 'Sisa tagihan invoice dihitung ulang setelah dihapus.',
                  confirmLabel: 'Hapus pembayaran',
                  destructive: true,
                });

                if (confirmed) {
                  router.delete(destroyPayment(item.id), { preserveScroll: true });
                }
              }}
            >
              <Trash2 className="size-4" />
            </Button>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

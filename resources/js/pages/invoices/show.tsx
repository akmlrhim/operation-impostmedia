import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { InvoiceShowActions } from '@/components/crm/invoices/invoice-show-actions';
import { InvoiceShowItemsCard } from '@/components/crm/invoices/invoice-show-items-card';
import { InvoiceShowPaymentsCard } from '@/components/crm/invoices/invoice-show-payments-card';
import { InvoiceShowSummaryCard } from '@/components/crm/invoices/invoice-show-summary-card';
import { PageHeader } from '@/components/crm/page-header';
import { PaymentFormModal } from '@/components/crm/payment-form-modal';
import { useRealtime } from '@/hooks/use-realtime';
import { stripQueryParams } from '@/lib/url';
import { destroy, index, show } from '@/routes/invoices';
import type { Invoice, Option } from '@/types/crm';

type Props = {
  invoice: Invoice;
  statuses: Option[];
  methods: Option[];
};

export default function InvoiceShow({ invoice, statuses, methods }: Props) {
  useRealtime(['invoices', 'attachments'], ['invoice']);

  const [confirm, confirmDialog] = useConfirm();
  const [paymentModal, setPaymentModal] = useState(
    () => new URLSearchParams(window.location.search).get('pay') === '1',
  );

  return (
    <>
      <Head title={invoice.number} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={invoice.number}
          actions={
            <InvoiceShowActions
              invoice={invoice}
              confirm={confirm}
              onPay={() => setPaymentModal(true)}
              onDelete={() => router.delete(destroy(invoice.id))}
            />
          }
        />

        <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
          <div className="space-y-4">
            <InvoiceShowItemsCard invoice={invoice} />
            <InvoiceShowPaymentsCard invoice={invoice} methods={methods} confirm={confirm} />
          </div>

          <InvoiceShowSummaryCard invoice={invoice} statuses={statuses} />
        </div>

        <AttachmentsCard type="invoices" id={invoice.id} attachments={invoice.attachments ?? []} />
      </div>

      {paymentModal && (
        <PaymentFormModal
          invoice={invoice}
          methods={methods}
          onClose={() => {
            setPaymentModal(false);
            stripQueryParams(['pay']);
          }}
        />
      )}

      {confirmDialog}
    </>
  );
}

InvoiceShow.layout = ({ invoice }: Props) => ({
  breadcrumbs: [
    { title: 'Invoice', href: index() },
    { title: invoice.number, href: show(invoice.id) },
  ],
});

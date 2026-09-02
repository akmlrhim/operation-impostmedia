import { Head } from '@inertiajs/react';
import { InvoiceForm } from '@/components/crm/invoice-form';
import { PageHeader } from '@/components/crm/page-header';
import { edit, index, show } from '@/routes/invoices';
import type {
  Client,
  CompanyProfile,
  ContractOption,
  Invoice,
  Option,
  ServiceOption,
} from '@/types/crm';

type Props = {
  invoice: Invoice;
  clients: Pick<Client, 'id' | 'company_name'>[];
  contracts: ContractOption[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
};

export default function InvoiceEdit({
  invoice,
  clients,
  contracts,
  services,
  company,
  types,
  statuses,
}: Props) {
  return (
    <>
      <Head title={`Ubah ${invoice.number}`} />

      <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <PageHeader title={`Ubah ${invoice.number}`} backHref={show(invoice.id).url} />

        <InvoiceForm
          invoice={invoice}
          suggestedNumber={invoice.number}
          clients={clients}
          contracts={contracts}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          cancelHref={show(invoice.id).url}
        />
      </div>
    </>
  );
}

InvoiceEdit.layout = ({ invoice }: Props) => ({
  breadcrumbs: [
    { title: 'Invoice', href: index() },
    { title: invoice.number, href: show(invoice.id) },
    { title: 'Ubah', href: edit(invoice.id) },
  ],
});

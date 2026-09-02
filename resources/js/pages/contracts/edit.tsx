import { Head } from '@inertiajs/react';
import { ContractForm } from '@/components/crm/contract-form';
import { PageHeader } from '@/components/crm/page-header';
import { edit, index, show } from '@/routes/contracts';
import type { Client, CompanyProfile, Contract, Option, ServiceOption } from '@/types/crm';

type Props = {
  contract: Contract;
  clients: Pick<Client, 'id' | 'company_name'>[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  aiScopePoints: boolean;
};

export default function ContractEdit({
  contract,
  clients,
  services,
  company,
  types,
  statuses,
  billingCycles,
  aiScopePoints,
}: Props) {
  return (
    <>
      <Head title={`Ubah ${contract.number}`} />

      <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <PageHeader title={`Ubah ${contract.number}`} backHref={show(contract.id).url} />

        <ContractForm
          contract={contract}
          suggestedNumber={contract.number}
          clients={clients}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          billingCycles={billingCycles}
          aiScopePoints={aiScopePoints}
          cancelHref={show(contract.id).url}
        />
      </div>
    </>
  );
}

ContractEdit.layout = ({ contract }: Props) => ({
  breadcrumbs: [
    { title: 'MoU & Kontrak', href: index() },
    { title: contract.number, href: show(contract.id) },
    { title: 'Ubah', href: edit(contract.id) },
  ],
});

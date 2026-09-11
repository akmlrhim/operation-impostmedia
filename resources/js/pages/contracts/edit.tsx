import { Head } from '@inertiajs/react';
import { ContractForm } from '@/components/crm/contract-form';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { edit, index, show } from '@/routes/contracts';
import type {
  Client,
  CompanyProfile,
  Contract,
  Option,
  ServiceOption,
  UserOption,
} from '@/types/crm';

type Props = {
  contract: Contract;
  clients: Pick<Client, 'id' | 'company_name'>[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  users: UserOption[];
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
  users,
  aiScopePoints,
}: Props) {
  return (
    <>
      <Head title={`Ubah ${contract.number}`} />

      <PageHeader title={`Ubah ${contract.number}`} backHref={show(contract.id).url} />

      <PageBody className="mx-auto w-full max-w-5xl">
        <ContractForm
          contract={contract}
          suggestedNumber={contract.number}
          clients={clients}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          users={users}
          billingCycles={billingCycles}
          aiScopePoints={aiScopePoints}
          cancelHref={show(contract.id).url}
        />
      </PageBody>
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

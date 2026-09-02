import type { LineItem } from '@/types/crm';

export type ContractFormData = {
  number: string;
  client_id: string;
  lead_id: string;
  type: string;
  title: string;
  scope: string;
  start_date: string;
  end_date: string;
  signing_place: string;
  signed_date: string;
  tax_percent: string;
  payment_terms: string;
  billing_cycle: string;
  next_invoice_date: string;
  first_party_name: string;
  first_party_position: string;
  second_party_name: string;
  second_party_position: string;
  status: string;
  items: LineItem[];
};

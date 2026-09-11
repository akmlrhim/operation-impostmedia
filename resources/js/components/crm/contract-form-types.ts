import type { LineItem } from '@/types/crm';

export type ContractFormData = {
  number: string;
  client_id: string;
  lead_id: string;
  type: string;
  title: string;
  start_date: string;
  end_date: string;
  signing_place: string;
  signed_date: string;
  discount_amount: string;
  tax_percent: string;
  billing_cycle: string;
  next_invoice_date: string;
  first_party_name: string;
  first_party_position: string;
  status: string;
  assigned_to_ids: number[];
  items: LineItem[];
};

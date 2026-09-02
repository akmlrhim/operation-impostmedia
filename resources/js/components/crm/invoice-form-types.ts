import type { LineItem } from '@/types/crm';

export type InvoiceFormData = {
  number: string;
  client_id: string;
  contract_id: string;
  type: string;
  issue_date: string;
  due_date: string;
  period_start: string;
  period_end: string;
  discount_amount: string;
  tax_percent: string;
  status: string;
  notes: string;
  items: LineItem[];
};

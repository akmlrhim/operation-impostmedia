export type Option = {
  value: string;
  label: string;
};

export type UserRef = {
  id: number;
  name: string;
};

export type Paginated<T> = {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
  from: number | null;
  to: number | null;
  total: number;
};

export type LeadCard = {
  id: number;
  company_name: string;
  contact_name: string;
  email: string | null;
  phone: string | null;
  source: string | null;
  estimated_value: number;
  priority: string;
  status: string;
  owner: UserRef | null;
  owner_id: number | null;
  lost_reason: string | null;
  expected_close_date: string | null;
  next_follow_up_at: string | null;
  converted_client_id: number | null;
  notes: string | null;
};

export type LeadStageColumn = {
  id: number;
  name: string;
  color: string;
  type: string;
  total: number;
  count: number;
  leads: LeadCard[];
};

export type AttachmentOwner = 'leads' | 'clients' | 'contracts' | 'invoices';

export type AttachmentItem = {
  id: number;
  name: string;
  mime_type: string | null;
  size: number;
  created_at: string;
  uploader?: UserRef | null;
};

export type ClientContractRef = {
  id: number;
  number: string;
  title: string;
  value: string;
  status: string;
};

export type ClientInvoiceRef = {
  id: number;
  number: string;
  total: string;
  balance_due: string;
  due_date: string;
  status: string;
};

export type Client = {
  id: number;
  short_code: string | null;
  company_name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  contact_name: string | null;
  contact_position: string | null;
  contact_email: string | null;
  contact_phone: string | null;
  status: string;
  account_manager_id: number | null;
  account_manager?: UserRef | null;
  notes: string | null;
  contracts_count?: number;
  attachments?: AttachmentItem[];
  invoices_count?: number;
  contracts?: ClientContractRef[];
  invoices?: ClientInvoiceRef[];
};

export type LineItem = {
  id?: number;
  service_package_id: number | null;
  name: string;
  description: string | null;
  quantity: number | string;
  unit: string;
  unit_price: number | string;
  amount?: number | string;
};

export type ContractClause = {
  id: string;
  topic: string;
  points: string[];
};

export type Contract = {
  id: number;
  number: string;
  client_id: number;
  lead_id: number | null;
  client?: Client;
  type: string;
  title: string;
  scope: string | null;
  body: string | null;
  start_date: string | null;
  end_date: string | null;
  signing_place: string | null;
  signed_date: string | null;
  subtotal: string;
  tax_percent: string;
  tax_amount: string;
  value: string;
  payment_terms: string | null;
  billing_cycle: string;
  next_invoice_date: string | null;
  first_party_name: string | null;
  first_party_position: string | null;
  second_party_name: string | null;
  second_party_position: string | null;
  status: string;
  items?: LineItem[];
  attachments?: AttachmentItem[];
  invoices?: Invoice[];
  invoices_count?: number;
};

export type Payment = {
  id: number;
  amount: string;
  paid_at: string;
  method: string;
  notes: string | null;
  recorder?: UserRef | null;
};

export type Invoice = {
  id: number;
  number: string;
  client_id: number;
  contract_id: number | null;
  client?: Client;
  contract?: { id: number; number: string; title: string } | null;
  type: string;
  issue_date: string;
  due_date: string;
  period_start: string | null;
  period_end: string | null;
  subtotal: string;
  discount_amount: string;
  tax_percent: string;
  tax_amount: string;
  total: string;
  amount_paid: string;
  balance_due: string;
  status: string;
  notes: string | null;
  items?: LineItem[];
  payments?: Payment[];
  payments_count?: number;
  attachments?: AttachmentItem[];
};

export type ContractOption = {
  id: number;
  number: string;
  title: string;
  client_id: number;
  is_recurring: boolean;
};

export type ServicePackagePoint = {
  id?: number;
  label: string;
};

export type ServicePackage = {
  id?: number;
  name: string;
  description: string | null;
  price: number | string;
  unit: string;
  billing_type: string;
  is_active: boolean;
  requires_visit: boolean;
  points: ServicePackagePoint[];
};

export type ServiceItem = {
  id: number;
  type: string;
  type_label: string;
  name: string;
  description: string | null;
  is_active: boolean;
  packages: ServicePackage[];
};

export type ServiceOption = {
  id: number;
  type: string;
  type_label: string;
  name: string;
  packages: {
    id: number;
    name: string;
    price: string;
    unit: string;
    billing_type: string;
    points: { id: number; label: string }[];
  }[];
};

export type CompanyProfile = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  signatory_name: string | null;
  signatory_position: string | null;
  invoice_notes: string | null;
  terms: string | null;
};

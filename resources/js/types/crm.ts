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
  date_in: string;
  company_name: string;
  industry: string | null;
  contact_name: string;
  email: string | null;
  phone: string | null;
  region: string | null;
  source: string | null;
  pic: string | null;
  pic_impost: string | null;
  service_packages: { id: number; name: string; price: number }[];
  last_invoice: { id: number; number: string; issue_date: string } | null;
  estimated_value: number;
  last_contact_date: string | null;
  next_action_date: string | null;
  next_action: string | null;
  temperature: string;
  notes: string | null;
  folder_url: string | null;
  status: string;
  lost_reason: string | null;
  converted_client_id: number | null;
};

export type LeadActivity = {
  id: number;
  type: string;
  type_label: string;
  title: string;
  description: string | null;
  scheduled_at: string | null;
  completed_at: string | null;
  created_at: string | null;
  user: UserRef | null;
};

export type LeadDetail = LeadCard & {
  stage: { id: number; name: string; color: string } | null;
  services: {
    id: number;
    name: string;
    price: number;
    unit: string;
    service_name: string | null;
  }[];
  converted_client: { id: number; company_name: string } | null;
  attachments: AttachmentItem[];
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
  status: string;
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
  client_id: number | null;
  lead_id: number | null;
  client?: Client;
  type: string;
  title: string;
  start_date: string | null;
  end_date: string | null;
  signing_place: string | null;
  signed_date: string | null;
  subtotal: string;
  discount_amount: string;
  tax_percent: string;
  tax_amount: string;
  value: string;
  billing_cycle: string;
  next_invoice_date: string | null;
  first_party_name: string | null;
  first_party_position: string | null;
  status: string;
  items?: LineItem[];
  attachments?: AttachmentItem[];
  invoices?: Invoice[];
  invoices_count?: number;
};

export type ContractGroup = {
  id: number | null;
  company_name: string;
  contracts: Contract[];
  contracts_count: number;
  contracts_value: number | string | null;
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
  client_id: number | null;
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

export type InvoiceGroup = {
  id: number | null;
  company_name: string;
  invoices: Invoice[];
  invoices_count: number;
  invoices_total: number | string | null;
  invoices_balance_due: number | string | null;
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

export type CompanyIdentity = {
  name: string;
  address: string;
  city: string;
  phone: string;
  email: string;
  signatory_name: string;
  signatory_position: string;
};

export type CompanyProfile = CompanyIdentity & {
  invoice_notes: string;
  terms: string;
};

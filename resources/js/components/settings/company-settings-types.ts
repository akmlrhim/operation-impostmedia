export type CompanyFormData = {
  name: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  signatory_name: string;
  signatory_position: string;
  invoice_notes: string;
  terms: string;
  logo: File | null;
  signature: File | null;
  stamp: File | null;
  remove_logo: boolean;
  remove_signature: boolean;
  remove_stamp: boolean;
};

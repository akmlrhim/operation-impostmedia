export type UserRole = 'superuser' | 'manager' | 'member';

export type UserCan = {
  'manage-services': boolean;
  'manage-lead-stages': boolean;
  'manage-company-settings': boolean;
  'create-leads': boolean;
  'update-leads': boolean;
  'move-leads': boolean;
  'convert-leads': boolean;
  'delete-leads': boolean;
  'export-leads': boolean;
  'create-clients': boolean;
  'update-clients': boolean;
  'delete-clients': boolean;
  'export-clients': boolean;
  'create-contracts': boolean;
  'update-contracts': boolean;
  'approve-contracts': boolean;
  'delete-contracts': boolean;
  'export-contracts': boolean;
  'create-invoices': boolean;
  'update-invoices': boolean;
  'delete-invoices': boolean;
  'export-invoices': boolean;
  'manage-payments': boolean;
  'view-finance': boolean;
  'manage-finance': boolean;
  'export-finance': boolean;
  'manage-users': boolean;
};

export type User = {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  email_verified_at: string | null;
  role: UserRole;
  approved_at: string | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
};

export type Auth = {
  user: User;
};

export type GoogleAccount = {
  email: string;
  avatar_url: string | null;
  synced_at: string | null;
};

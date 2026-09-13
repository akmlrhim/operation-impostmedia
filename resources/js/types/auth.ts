export type UserRole = 'superuser' | 'manager' | 'member';

export type UserCan = {
  'manage-users': boolean;
  'manage-master-data': boolean;
  'manage-finance': boolean;
  'approve-documents': boolean;
  'manage-records': boolean;
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

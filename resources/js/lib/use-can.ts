import { usePage } from '@inertiajs/react';
import type { UserCan } from '@/types';

export function useCan(): UserCan {
  const { can } = usePage().props;

  return {
    'manage-users': can['manage-users'] ?? false,
    'manage-master-data': can['manage-master-data'] ?? false,
    'manage-finance': can['manage-finance'] ?? false,
    'approve-documents': can['approve-documents'] ?? false,
    'manage-records': can['manage-records'] ?? false,
  };
}

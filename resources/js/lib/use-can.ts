import { usePage } from '@inertiajs/react';
import type { UserCan } from '@/types';

export function useCan(): UserCan {
  const { can } = usePage().props;

  return can;
}

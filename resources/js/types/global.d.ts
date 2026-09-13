import type { Auth, UserCan } from '@/types/auth';
import type { NotificationFeed } from '@/types/notification';

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: {
      name: string;
      auth: Auth;
      can: UserCan;
      sidebarOpen: boolean;
      notifications: NotificationFeed;
      [key: string]: unknown;
    };
  }
}

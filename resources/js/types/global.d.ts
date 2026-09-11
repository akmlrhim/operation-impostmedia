import type { Auth } from '@/types/auth';
import type { NotificationFeed } from '@/types/notification';

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: {
      name: string;
      auth: Auth;
      sidebarOpen: boolean;
      notifications: NotificationFeed;
      [key: string]: unknown;
    };
  }
}

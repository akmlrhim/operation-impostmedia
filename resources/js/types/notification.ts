export type AppNotification = {
  id: string;
  kind: string;
  title: string;
  subtitle: string;
  url: string;
  at: string | null;
  read: boolean;
};

export type NotificationFeed = {
  unread: number;
  items: AppNotification[];
};

export type AttentionItem = {
  kind: 'invoice' | 'lead' | 'contract';
  id: number;
  title: string;
  subtitle: string;
  date: string;
  amount: number | null;
  severity: 'critical' | 'serious' | 'warning';
};

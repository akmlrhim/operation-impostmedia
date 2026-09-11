import type { UserRef } from '@/types/crm';

export type FinanceTransaction = {
  id: number;
  type: 'income' | 'expense';
  category: string;
  amount: string;
  transaction_date: string;
  notes: string | null;
  recorder?: UserRef | null;
};

export type FinanceChartPoint = {
  key: string;
  label: string;
  income: number;
  expense: number;
};

export type FinanceSummary = {
  income: number;
  expense: number;
  net: number;
};

const rupiahFormatter = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
});

const compactFormatter = new Intl.NumberFormat('id-ID', {
  notation: 'compact',
  compactDisplay: 'short',
  maximumFractionDigits: 1,
});

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
});

const shortDateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
});

const longDateFormatter = new Intl.DateTimeFormat('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

export function rupiah(value: number | string | null | undefined): string {
  return rupiahFormatter.format(Number(value ?? 0));
}

export function rupiahCompact(value: number | string | null | undefined): string {
  return `Rp${compactFormatter.format(Number(value ?? 0))}`;
}

export function decimal(value: number | string | null | undefined): string {
  return new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
  }).format(Number(value ?? 0));
}

export function fileSize(bytes: number): string {
  if (bytes < 1024) {
    return `${bytes} B`;
  }

  const units = ['KB', 'MB', 'GB'];
  let value = bytes / 1024;
  let unit = 0;

  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit++;
  }

  return `${decimal(Math.round(value * 10) / 10)} ${units[unit]}`;
}

export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '-';
  }

  return dateFormatter.format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) {
    return '-';
  }

  return dateTimeFormatter.format(new Date(value));
}

export function formatLongDate(value: string | null | undefined): string {
  if (!value) {
    return '-';
  }

  return longDateFormatter.format(new Date(value));
}

export function shortDate(value: string | null | undefined): string {
  if (!value) {
    return '-';
  }

  return shortDateFormatter.format(new Date(value));
}

export function daysFromToday(value: string | null | undefined): number | null {
  if (!value) {
    return null;
  }

  const target = new Date(value);
  const today = new Date();
  target.setHours(0, 0, 0, 0);
  today.setHours(0, 0, 0, 0);

  return Math.round((target.getTime() - today.getTime()) / 86_400_000);
}

const relativeFormatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' });

export function relativeTime(value: string | null | undefined): string {
  if (!value) {
    return '-';
  }

  const seconds = (Date.now() - new Date(value).getTime()) / 1000;
  const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31_536_000],
    ['month', 2_592_000],
    ['day', 86_400],
    ['hour', 3600],
    ['minute', 60],
  ];

  for (const [unit, size] of units) {
    if (seconds >= size) {
      return relativeFormatter.format(-Math.floor(seconds / size), unit);
    }
  }

  return 'baru saja';
}

export function relativeDueLabel(value: string | null | undefined): string {
  const days = daysFromToday(value);

  if (days === null) {
    return '-';
  }

  if (days === 0) {
    return 'Hari ini';
  }

  if (days === 1) {
    return 'Besok';
  }

  if (days === -1) {
    return 'Kemarin';
  }

  return days < 0 ? `Telat ${Math.abs(days)} hari` : `${days} hari lagi`;
}

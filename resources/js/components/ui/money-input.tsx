import { useEffect, useLayoutEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

function group(digits: string): string {
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function normalize(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  const raw = String(value);

  if (typeof value === 'number' || raw.includes('.') || raw.includes(',')) {
    const parsed = Number(raw.replace(',', '.'));

    return Number.isFinite(parsed) ? String(Math.trunc(Math.abs(parsed))) : '';
  }

  return raw.replace(/\D/g, '');
}

function caretAfterDigit(formatted: string, digitCount: number): number {
  if (digitCount === 0) {
    return 0;
  }

  let seen = 0;

  for (let index = 0; index < formatted.length; index++) {
    if (/\d/.test(formatted[index])) {
      seen++;

      if (seen === digitCount) {
        return index + 1;
      }
    }
  }

  return formatted.length;
}

export type MoneyInputProps = {
  id?: string;
  value: string | number | null;
  onChange: (value: string) => void;
  min?: number;
  required?: boolean;
  disabled?: boolean;
  invalid?: boolean;
  placeholder?: string;
  className?: string;
  'aria-label'?: string;
};

export function MoneyInput({
  id,
  value,
  onChange,
  min,
  required,
  disabled,
  invalid,
  placeholder,
  className,
  'aria-label': ariaLabel,
}: MoneyInputProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const caretRef = useRef<number | null>(null);

  const digits = normalize(value);
  const display = group(digits);

  useLayoutEffect(() => {
    if (caretRef.current !== null && inputRef.current) {
      inputRef.current.setSelectionRange(caretRef.current, caretRef.current);
      caretRef.current = null;
    }
  });

  useEffect(() => {
    const control = inputRef.current;

    if (!control) {
      return;
    }

    const below = min !== undefined && digits !== '' && Number(digits) < min;

    control.setCustomValidity(below ? `Minimal Rp${group(String(min))}.` : '');
  }, [digits, min]);

  function handleChange(event: React.ChangeEvent<HTMLInputElement>) {
    const raw = event.target.value;
    const caret = event.target.selectionStart ?? raw.length;

    const typed = raw.replace(/\D/g, '');
    const next = typed.replace(/^0+(?=\d)/, '');
    const dropped = typed.length - next.length;
    const digitsBeforeCaret = Math.max(0, raw.slice(0, caret).replace(/\D/g, '').length - dropped);

    caretRef.current = caretAfterDigit(group(next), digitsBeforeCaret);
    onChange(next);
  }

  return (
    <div
      data-slot="money-input"
      className={cn(
        'flex h-8 w-full min-w-0 items-center gap-1.5 rounded-md border border-input bg-background px-2.5 py-1 shadow-xs transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
        invalid && 'border-destructive ring-destructive/20 dark:ring-destructive/40',
        disabled && 'pointer-events-none cursor-not-allowed opacity-50',
        className,
      )}
    >
      <span aria-hidden className="shrink-0 text-sm text-muted-foreground select-none">
        Rp
      </span>

      <input
        ref={inputRef}
        id={id}
        type="text"
        inputMode="numeric"
        autoComplete="off"
        value={display}
        onChange={handleChange}
        required={required}
        disabled={disabled}
        placeholder={placeholder}
        aria-label={ariaLabel}
        aria-invalid={invalid}
        className="w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted-foreground md:text-sm"
      />
    </div>
  );
}

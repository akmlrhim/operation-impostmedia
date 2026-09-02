import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        'flex shrink-0 items-center justify-center rounded-md bg-white p-1 shadow-xs',
        className,
      )}
      {...props}
    >
      <img
        src="/logo_original.webp"
        alt="Operation Impact Media"
        className="size-full object-contain"
      />
    </div>
  );
}

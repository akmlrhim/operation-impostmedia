import { ImageIcon, Trash2, Upload } from 'lucide-react';
import { useEffect, useMemo } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export function ImageUpload({
  id,
  currentUrl,
  file,
  removed,
  onSelect,
  onRemove,
  emptyLabel = 'Belum ada gambar',
  className,
}: {
  id: string;
  currentUrl: string | null;
  file: File | null;
  removed: boolean;
  onSelect: (file: File | null) => void;
  onRemove: () => void;
  emptyLabel?: string;
  className?: string;
}) {
  const previewUrl = useMemo(() => (file === null ? null : URL.createObjectURL(file)), [file]);

  useEffect(() => {
    if (previewUrl === null) {
      return;
    }

    return () => URL.revokeObjectURL(previewUrl);
  }, [previewUrl]);

  const shown = previewUrl ?? (removed ? null : currentUrl);

  return (
    <div className={cn('flex items-center gap-3', className)}>
      <div className="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted/40">
        {shown ? (
          <img src={shown} alt="" className="size-full object-contain p-1" />
        ) : (
          <ImageIcon className="size-5 text-muted-foreground" aria-hidden />
        )}
      </div>

      <div className="grid gap-1.5">
        <div className="flex flex-wrap items-center gap-2">
          <Button type="button" variant="outline" size="sm" asChild>
            <label htmlFor={id} className="cursor-pointer">
              <Upload className="size-3.5" />
              {shown ? 'Ganti' : 'Pilih gambar'}
            </label>
          </Button>

          {shown && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="text-muted-foreground hover:text-destructive"
              onClick={() => {
                onSelect(null);
                onRemove();
              }}
            >
              <Trash2 className="size-3.5" />
              Hapus
            </Button>
          )}
        </div>

        <p className="text-xs text-muted-foreground">
          {shown ? 'PNG atau JPG, maksimal 2 MB.' : emptyLabel}
        </p>
      </div>

      <input
        id={id}
        type="file"
        accept="image/png,image/jpeg,image/webp"
        className="sr-only"
        onChange={(event) => onSelect(event.target.files?.[0] ?? null)}
      />
    </div>
  );
}

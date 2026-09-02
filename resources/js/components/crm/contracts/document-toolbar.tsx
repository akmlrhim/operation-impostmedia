import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Italic,
  List,
  ListOrdered,
  Redo2,
  RemoveFormatting,
  Strikethrough,
  Underline,
  Undo2,
} from 'lucide-react';
import type { ComponentType } from 'react';
import type { DocumentEditorHandle } from '@/components/crm/contracts/document-editor';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Toggle } from '@/components/ui/toggle';

const MARKS: { command: string; label: string; icon: ComponentType<{ className?: string }> }[] = [
  { command: 'bold', label: 'Tebal', icon: Bold },
  { command: 'italic', label: 'Miring', icon: Italic },
  { command: 'underline', label: 'Garis bawah', icon: Underline },
  { command: 'strikeThrough', label: 'Coret', icon: Strikethrough },
];

const BLOCKS: { command: string; label: string; icon: ComponentType<{ className?: string }> }[] = [
  { command: 'insertUnorderedList', label: 'Daftar berbutir', icon: List },
  { command: 'insertOrderedList', label: 'Daftar bernomor', icon: ListOrdered },
];

const ALIGN: { command: string; label: string; icon: ComponentType<{ className?: string }> }[] = [
  { command: 'justifyLeft', label: 'Rata kiri', icon: AlignLeft },
  { command: 'justifyCenter', label: 'Rata tengah', icon: AlignCenter },
  { command: 'justifyRight', label: 'Rata kanan', icon: AlignRight },
  { command: 'justifyFull', label: 'Rata kiri-kanan', icon: AlignJustify },
];

const STYLES = [
  { value: 'p', label: 'Teks biasa' },
  { value: 'h1', label: 'Judul besar' },
  { value: 'h2', label: 'Judul sedang' },
  { value: 'h3', label: 'Judul kecil' },
];

const LIST_STYLES = [
  { value: 'disc', label: '• Bulat' },
  { value: 'circle', label: '◦ Lingkaran' },
  { value: 'square', label: '▪ Kotak' },
  { value: 'decimal', label: '1. Angka' },
  { value: 'decimal-leading-zero', label: '01. Angka nol' },
  { value: 'lower-alpha', label: 'a. Huruf kecil' },
  { value: 'upper-alpha', label: 'A. Huruf besar' },
  { value: 'lower-roman', label: 'i. Angka romawi kecil' },
  { value: 'upper-roman', label: 'I. Angka romawi besar' },
  { value: 'none', label: 'Tanpa tanda' },
];

export function DocumentToolbar({
  editor,
  version,
}: {
  editor: DocumentEditorHandle | null;
  version: number;
}) {
  const active = (command: string): boolean => (editor === null ? false : editor.isActive(command));

  void version;

  return (
    <div className="sticky top-0 z-10 flex flex-wrap items-center gap-1 rounded-lg border bg-background/95 p-1.5 backdrop-blur">
      <Select value="" onValueChange={(value) => editor?.command('formatBlock', `<${value}>`)}>
        <SelectTrigger className="h-8 w-36" aria-label="Gaya paragraf">
          <SelectValue placeholder="Gaya teks" />
        </SelectTrigger>
        <SelectContent>
          {STYLES.map((style) => (
            <SelectItem key={style.value} value={style.value}>
              {style.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Separator orientation="vertical" className="mx-1 h-6" />

      {MARKS.map(({ command, label, icon: Icon }) => (
        <Toggle
          key={command}
          size="sm"
          className="h-8 w-8 p-0"
          aria-label={label}
          pressed={active(command)}
          onPressedChange={() => editor?.command(command)}
        >
          <Icon className="size-4" />
        </Toggle>
      ))}

      <Separator orientation="vertical" className="mx-1 h-6" />

      {BLOCKS.map(({ command, label, icon: Icon }) => (
        <Toggle
          key={command}
          size="sm"
          className="h-8 w-8 p-0"
          aria-label={label}
          pressed={active(command)}
          onPressedChange={() => editor?.command(command)}
        >
          <Icon className="size-4" />
        </Toggle>
      ))}

      <Select value="" onValueChange={(value) => editor?.listStyle(value)}>
        <SelectTrigger className="h-8 w-40" aria-label="Gaya daftar">
          <SelectValue placeholder="Gaya daftar" />
        </SelectTrigger>
        <SelectContent>
          {LIST_STYLES.map((style) => (
            <SelectItem key={style.value} value={style.value}>
              {style.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Separator orientation="vertical" className="mx-1 h-6" />

      {ALIGN.map(({ command, label, icon: Icon }) => (
        <Toggle
          key={command}
          size="sm"
          className="h-8 w-8 p-0"
          aria-label={label}
          pressed={active(command)}
          onPressedChange={() => editor?.command(command)}
        >
          <Icon className="size-4" />
        </Toggle>
      ))}

      <Separator orientation="vertical" className="mx-1 h-6" />

      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="size-8"
        aria-label="Bersihkan format"
        onClick={() => editor?.command('removeFormat')}
      >
        <RemoveFormatting className="size-4" />
      </Button>

      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="size-8"
        aria-label="Urungkan"
        onClick={() => editor?.command('undo')}
      >
        <Undo2 className="size-4" />
      </Button>

      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="size-8"
        aria-label="Ulangi"
        onClick={() => editor?.command('redo')}
      >
        <Redo2 className="size-4" />
      </Button>
    </div>
  );
}

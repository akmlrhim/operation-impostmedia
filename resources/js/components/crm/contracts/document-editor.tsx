import { useCallback, useEffect, useRef, useState } from 'react';

const EDITABLE_SELECTOR = '.doc-body';

// `instanceof HTMLElement` fails across the iframe's separate window realm,
// so walk up using nodeName instead.
function closestList(node: Node | null): HTMLElement | null {
  while (node) {
    if (node.nodeType === Node.ELEMENT_NODE && (node.nodeName === 'UL' || node.nodeName === 'OL')) {
      return node as HTMLElement;
    }

    node = node.parentNode;
  }

  return null;
}

export function DocumentEditor({
  page,
  onReady,
  onDirty,
  onSelection,
}: {
  page: string;
  onReady: (handle: DocumentEditorHandle) => void;
  onDirty: () => void;
  onSelection: () => void;
}) {
  const frame = useRef<HTMLIFrameElement>(null);
  const [height, setHeight] = useState(1200);

  const attach = useCallback(() => {
    const doc = frame.current?.contentDocument;
    const body = doc?.querySelector<HTMLElement>(EDITABLE_SELECTOR);

    if (!doc || !body) {
      return;
    }

    body.contentEditable = 'true';
    body.spellcheck = false;
    body.style.outline = 'none';
    body.style.minHeight = '120mm';

    doc.execCommand('styleWithCSS', false, 'false');

    const measure = () => setHeight(doc.documentElement.scrollHeight + 24);

    measure();
    body.addEventListener('input', onDirty);
    body.addEventListener('input', measure);

    doc.addEventListener('selectionchange', onSelection);

    onReady({
      command: (name: string, value?: string) => {
        body.focus();
        doc.execCommand(name, false, value);
        onDirty();
        measure();
      },
      isActive: (name: string) => {
        try {
          return doc.queryCommandState(name);
        } catch {
          return false;
        }
      },
      listStyle: (type: string) => {
        body.focus();

        let list = closestList(doc.getSelection()?.anchorNode ?? null);

        if (!list) {
          doc.execCommand('insertUnorderedList', false);
          list = closestList(doc.getSelection()?.anchorNode ?? null);
        }

        if (list) {
          list.style.listStyleType = type;
        }

        onDirty();
        measure();
      },
      html: () => body.innerHTML,
    });
  }, [onReady, onDirty, onSelection]);

  useEffect(() => {
    const iframe = frame.current;

    if (iframe === null) {
      return;
    }

    // `srcDoc` can finish parsing before this effect runs (React flushes
    // effects for the whole tree after commit, and the iframe starts
    // loading as soon as it's inserted). If that happens the 'load'
    // event fires and is missed, leaving the document permanently
    // non-editable. Attach immediately when that's already the case.
    if (iframe.contentDocument?.readyState === 'complete') {
      attach();
    }

    iframe.addEventListener('load', attach);

    return () => iframe.removeEventListener('load', attach);
  }, [attach]);

  return (
    <iframe
      ref={frame}
      title="Isi dokumen"
      srcDoc={page}
      className="w-full rounded-lg border bg-white"
      style={{ height }}
    />
  );
}

export type DocumentEditorHandle = {
  command: (name: string, value?: string) => void;
  isActive: (name: string) => boolean;
  listStyle: (type: string) => void;
  html: () => string;
};

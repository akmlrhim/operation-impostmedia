import { GripVertical } from 'lucide-react';
import type { ReactNode } from 'react';
import { DropGhost, FlipBox } from '@/components/crm/kanban-flip-box';
import { useKanbanState } from '@/components/crm/kanban-state';
import type { KanbanColumn } from '@/components/crm/kanban-types';
import { cn } from '@/lib/utils';

export type { KanbanColumn };

const DRAG_IMAGE_SHADOW = '0 24px 48px -16px rgba(0,0,0,0.35), 0 8px 16px -8px rgba(0,0,0,0.18)';

function setLiftedDragImage(event: React.DragEvent<HTMLElement>, source: HTMLElement) {
  const rect = source.getBoundingClientRect();
  const clone = source.cloneNode(true) as HTMLElement;

  clone.style.position = 'fixed';
  clone.style.top = '-9999px';
  clone.style.left = '-9999px';
  clone.style.width = `${rect.width}px`;
  clone.style.height = `${rect.height}px`;
  clone.style.margin = '0';
  clone.style.pointerEvents = 'none';
  clone.style.opacity = '1';
  clone.style.transform = 'scale(1.03)';
  clone.style.boxShadow = DRAG_IMAGE_SHADOW;

  document.body.appendChild(clone);
  event.dataTransfer.setDragImage(clone, event.clientX - rect.left, event.clientY - rect.top);
  setTimeout(() => clone.remove(), 0);
}

export function Kanban<T>({
  columns,
  getItemId,
  getItemColumnId,
  renderItem,
  renderHeader,
  renderEmpty,
  onMove,
  onReorderColumns,
  trailing,
}: {
  columns: KanbanColumn<T>[];
  getItemId: (item: T) => number;
  getItemColumnId: (item: T) => number;
  renderItem: (item: T) => ReactNode;
  renderHeader: (column: KanbanColumn<T>) => ReactNode;
  renderEmpty?: (column: KanbanColumn<T>) => ReactNode;
  onMove: (itemId: number, columnId: number, position: number) => void;
  onReorderColumns?: (ids: number[]) => void;
  trailing?: ReactNode;
}) {
  const {
    containerRef,
    renderedColumns,
    rects,
    settledIds,
    justMovedId,
    draggingHeight,
    setDraggingHeight,
    target,
    setTarget,
    setDragging,
    draggingColumn,
    startColumnDrag,
    trackColumnPointer,
    finishColumnDrag,
    dragging,
    reset,
    handleDrop,
  } = useKanbanState({ columns, getItemId, getItemColumnId, onMove, onReorderColumns });

  return (
    <div
      ref={containerRef}
      className="relative flex h-full gap-4 overflow-x-auto pb-2"
      onDragOver={(event) => {
        if (draggingColumn !== null) {
          event.preventDefault();
          event.dataTransfer.dropEffect = 'move';
          trackColumnPointer(event.clientX);
        }
      }}
      onDrop={(event) => {
        if (draggingColumn !== null) {
          event.preventDefault();
          finishColumnDrag(true);
        }
      }}
    >
      {renderedColumns.map((column) => {
        const isTargetColumn = target?.columnId === column.id;

        return (
          <FlipBox
            key={column.id}
            id={`col:${column.id}`}
            data-kanban-column=""
            rects={rects}
            disabled={settledIds.has(`col:${column.id}`)}
            className={cn(
              'flex h-full w-76 shrink-0 flex-col rounded-xl transition-colors duration-150 motion-reduce:transition-none',
              draggingColumn === column.id && 'opacity-45',
            )}
            onDragOver={(event) => {
              event.preventDefault();

              if (draggingColumn !== null) {
                return;
              }

              if (!isTargetColumn) {
                setTarget({
                  columnId: column.id,
                  index: column.items.length,
                });
              }
            }}
            onDrop={(event) => {
              if (draggingColumn !== null) {
                return;
              }

              event.preventDefault();
              handleDrop(column.id);
            }}
          >
            <header
              draggable={onReorderColumns !== undefined}
              title={
                onReorderColumns !== undefined ? 'Geser untuk mengubah urutan kolom' : undefined
              }
              onDragStart={(event) => {
                if (onReorderColumns === undefined) {
                  return;
                }

                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', `column:${column.id}`);
                setLiftedDragImage(event, event.currentTarget);
                startColumnDrag(column.id);
              }}
              onDragEnd={() => finishColumnDrag(false)}
              className={cn(
                'flex shrink-0 items-center gap-2 px-2 pb-2',
                onReorderColumns !== undefined && 'cursor-grab active:cursor-grabbing',
              )}
            >
              {onReorderColumns !== undefined && (
                <GripVertical aria-hidden className="size-3.5 shrink-0 text-muted-foreground/40" />
              )}

              {renderHeader(column)}
            </header>

            <div
              className={cn(
                'flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto rounded-xl p-1 transition-colors duration-150 motion-reduce:transition-none',
                isTargetColumn && 'bg-muted/70',
              )}
            >
              {column.items.length === 0 &&
                (isTargetColumn ? <DropGhost height={draggingHeight} /> : renderEmpty?.(column))}

              {column.items.map((item, index) => {
                const id = getItemId(item);
                const isDragged = dragging !== null && getItemId(dragging) === id;

                return (
                  <div key={id}>
                    {isTargetColumn && target?.index === index && (
                      <DropGhost height={draggingHeight} />
                    )}

                    <FlipBox
                      id={`card:${id}`}
                      rects={rects}
                      disabled={
                        dragging !== null || draggingColumn !== null || settledIds.has(`card:${id}`)
                      }
                      elevateOnMove={justMovedId === `card:${id}`}
                      draggable
                      onDragStart={(event) => {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', String(id));
                        setDraggingHeight(event.currentTarget.getBoundingClientRect().height);
                        setDragging(item);
                        setLiftedDragImage(event, event.currentTarget);
                      }}
                      onDragEnd={reset}
                      onDragOver={(event) => {
                        if (draggingColumn !== null) {
                          return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        const rect = event.currentTarget.getBoundingClientRect();
                        const isBottomHalf = event.clientY > rect.top + rect.height / 2;
                        const nextIndex = isBottomHalf ? index + 1 : index;

                        setTarget((current) =>
                          current?.columnId === column.id && current.index === nextIndex
                            ? current
                            : { columnId: column.id, index: nextIndex },
                        );
                      }}
                      className={cn(
                        'cursor-grab rounded-xl outline-2 outline-offset-2 outline-transparent transition-[opacity,outline-color,transform] duration-150 outline-dashed active:cursor-grabbing motion-reduce:transition-none',
                        isDragged && 'opacity-35 outline-primary/30',
                      )}
                    >
                      {renderItem(item)}
                    </FlipBox>
                  </div>
                );
              })}

              {isTargetColumn &&
                target?.index === column.items.length &&
                column.items.length > 0 && <DropGhost height={draggingHeight} />}
            </div>
          </FlipBox>
        );
      })}

      {trailing}
    </div>
  );
}

export type KanbanColumn<T> = {
  id: number;
  items: T[];
};

export type DropTarget = {
  columnId: number;
  index: number;
};

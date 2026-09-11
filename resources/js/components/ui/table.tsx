import * as React from 'react';
import { cn } from '@/lib/utils';

function Table({ className, ...props }: React.ComponentProps<'table'>) {
    return (
        <div
            data-slot="table-container"
            className="scroll-shadow-x relative w-full overflow-x-auto overscroll-x-contain"
        >
            <table
                data-slot="table"
                className={cn(
                    'w-full caption-bottom text-sm',
                    '[&_tr>*:first-child]:pl-3 [&_tr>*:last-child]:pr-3 [&_tr>*:last-child]:border-r-0',
                    className,
                )}
                {...props}
            />
        </div>
    );
}

function TableHeader({ className, ...props }: React.ComponentProps<'thead'>) {
    return (
        <thead
            data-slot="table-header"
            className={cn(
                'bg-panel-header [&_tr]:border-b [&_tr]:border-border',
                className,
            )}
            {...props}
        />
    );
}

function TableBody({ className, ...props }: React.ComponentProps<'tbody'>) {
    return (
        <tbody
            data-slot="table-body"
            className={cn('[&_tr:last-child]:border-0', className)}
            {...props}
        />
    );
}

function TableFooter({ className, ...props }: React.ComponentProps<'tfoot'>) {
    return (
        <tfoot
            data-slot="table-footer"
            className={cn(
                'border-t bg-panel-header font-medium [&>tr]:last:border-b-0',
                className,
            )}
            {...props}
        />
    );
}

function TableRow({ className, ...props }: React.ComponentProps<'tr'>) {
    return (
        <tr
            data-slot="table-row"
            className={cn(
                'border-b border-border/60 transition-colors duration-100 hover:bg-accent/60 data-[state=selected]:bg-accent',
                className,
            )}
            {...props}
        />
    );
}

function TableHead({ className, ...props }: React.ComponentProps<'th'>) {
    return (
        <th
            data-slot="table-head"
            className={cn(
                'h-8 border-r border-border/60 px-2.5 text-left align-middle text-[0.6875rem] leading-none font-semibold tracking-[0.06em] text-muted-foreground uppercase whitespace-nowrap [&:has([role=checkbox])]:pr-0',
                className,
            )}
            {...props}
        />
    );
}

function TableCell({ className, ...props }: React.ComponentProps<'td'>) {
    return (
        <td
            data-slot="table-cell"
            className={cn(
                'border-r border-border/60 px-2.5 py-1.5 align-middle [&:has([role=checkbox])]:pr-0',
                className,
            )}
            {...props}
        />
    );
}

function TableCaption({
    className,
    ...props
}: React.ComponentProps<'caption'>) {
    return (
        <caption
            data-slot="table-caption"
            className={cn('mt-4 text-sm text-muted-foreground', className)}
            {...props}
        />
    );
}

export {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
};

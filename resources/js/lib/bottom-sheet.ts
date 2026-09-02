export const bottomSheetOnMobile = [
  'top-auto bottom-0 left-0 max-h-[92dvh] w-full max-w-none translate-x-0 translate-y-0',
  'rounded-t-2xl rounded-b-none',
  'data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
  'sm:top-1/2 sm:bottom-auto sm:left-1/2 sm:max-h-[85vh] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-lg',
  'sm:data-[state=closed]:slide-out-to-bottom-0 sm:data-[state=open]:slide-in-from-bottom-0',
].join(' ');

export const bottomSheetHandle = 'mx-auto h-1.5 w-12 rounded-full bg-muted-foreground/25 sm:hidden';

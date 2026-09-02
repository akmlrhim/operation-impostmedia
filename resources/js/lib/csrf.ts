export function xsrfToken(): string {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

  return match === null ? '' : decodeURIComponent(match[1]);
}

export function jsonPostHeaders(): Record<string, string> {
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-XSRF-TOKEN': xsrfToken(),
    'X-Requested-With': 'XMLHttpRequest',
  };
}

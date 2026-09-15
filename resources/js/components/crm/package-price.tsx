import { decimal, rupiah, unitPriceSuffix } from '@/lib/format';

export function PackagePrice({
  price,
  quantity,
  unit,
}: {
  price: number | string | null | undefined;
  quantity?: number | string | null;
  unit: string;
}) {
  const count = Number(quantity ?? 0);

  if (price === null || price === undefined) {
    return <span>Custom</span>;
  }

  if (count > 1) {
    return (
      <span>
        {rupiah(price)}
        <span className="text-muted-foreground">
          {' '}
          · {decimal(count)} {unit}
        </span>
      </span>
    );
  }

  const suffix = unitPriceSuffix(price, unit);

  return (
    <span>
      {rupiah(price)}
      {suffix && <span className="text-muted-foreground">{suffix}</span>}
    </span>
  );
}

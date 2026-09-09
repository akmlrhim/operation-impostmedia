@php
    /** Format uang mengikuti contoh invoice: IDR 8,000,000.00 */
    $money = fn ($n) => 'IDR '.number_format((float) $n, 2, '.', ',');

    /** Tanggal versi Inggris seperti pada contoh: Aug 7, 2026 */
    $tanggal = fn ($d) => $d?->format('M j, Y') ?? '-';

    $bill = $invoice->billing_snapshot ?? [];

    /**
     * Catatan diambil dari data perusahaan. Kolom notes di invoice hanya dipakai
     * kalau memang diisi khusus untuk invoice itu -- form invoice sendiri sudah
     * mengisinya dari invoice_notes, jadi invoice lama yang notes-nya kosong tetap
     * ikut mencetak catatan standar perusahaan.
     */
    $catatan = filled($invoice->notes) ? $invoice->notes : $profile['invoice_notes'];

    /** Setiap baris deskripsi jadi satu poin -- ditulis pengguna satu poin per baris. */
    $bullets = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
        ->map(fn ($line) => trim($line))
        ->filter();

    /**
     * DomPDF tidak punya Arial: 'Arial' di CSS diam-diam jatuh ke Helvetica bawaan PDF,
     * dan karakter yang tidak ada di sana lari ke DejaVu Sans -- itulah kenapa hasil
     * cetak terlihat memakai lebih dari satu font. Berkas .ttf asli didaftarkan lewat
     * @font-face supaya seluruh dokumen benar-benar satu font.
     */
    $fontDir = 'file://'.str_replace('\\', '/', resource_path('fonts'));

    /**
     * DomPDF mengalikan line-height dengan tinggi alami font x font_height_ratio
     * (untuk Arial ~1,267x), jadi nilai yang ditulis di CSS keluar jauh lebih longgar
     * di PDF. $lh() menerima jarak baris yang benar-benar diinginkan lalu membaginya
     * balik. Kalau suatu saat blade ini dirender browser lagi, pembagian ini harus
     * dimatikan untuk jalur tersebut.
     */
    $lh = fn (float $pt) => round($pt / 1.267, 2).'pt';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>

    <style>
        @font-face { font-family: 'Arial'; font-style: normal; font-weight: normal; src: url("{{ $fontDir }}/arial.ttf") format("truetype"); }
        @font-face { font-family: 'Arial'; font-style: normal; font-weight: bold;   src: url("{{ $fontDir }}/arialbd.ttf") format("truetype"); }
        @font-face { font-family: 'Arial'; font-style: italic; font-weight: normal; src: url("{{ $fontDir }}/ariali.ttf") format("truetype"); }
        @font-face { font-family: 'Arial'; font-style: italic; font-weight: bold;   src: url("{{ $fontDir }}/arialbi.ttf") format("truetype"); }

        @page { size: A4; margin: 9mm 16mm 10mm; }

        * { font-family: 'Arial'; }
        body { margin: 0; font-family: 'Arial'; font-size: 10pt; line-height: {{ $lh(14) }}; color: #171717; }
        table { border-collapse: collapse; }
        .muted { color: #737373; }

        /* Kop: logo di kiri, judul + tanggal + balance due menumpuk di kanan. */
        .masthead { width: 100%; }
        .masthead .col-left { width: 52%; vertical-align: top; padding: 0; }
        .masthead .col-right { width: 48%; vertical-align: top; padding: 0; }

        .logo { display: block; max-height: 104px; max-width: 152px; }

        .doc-title { margin: 0; font-size: 24pt; font-weight: normal; letter-spacing: 1px; color: #3f3f46; text-transform: uppercase; text-align: right; }
        .doc-number { margin-top: 4px; text-align: right; color: #737373; }

        .meta { width: 100%; margin-top: 30px; }
        .meta td { padding: 4px 0; }
        .meta td.label { text-align: right; color: #737373; padding-right: 24px; }
        .meta td.value { width: 50%; text-align: right; }
        .meta tr.balance td { background: #f3f4f6; padding-top: 7px; padding-bottom: 7px; font-weight: bold; color: #171717; }
        .meta tr.balance td.label { padding-left: 12px; }

        .company { margin-top: 22px; }
        .company .name { font-weight: bold; }
        .company .address { margin-top: 12px; color: #737373; line-height: {{ $lh(12.7) }}; }

        .bill-to { margin-top: 16px; }
        .bill-to .label { color: #737373; }
        .bill-to .name { margin-top: 6px; font-weight: bold; }

        /* Rincian item. */
        table.items { width: 100%; margin-top: 30px; }
        table.items thead tr { background: #3f3f46; }
        table.items th { padding: 5px 12px; font-size: 9pt; line-height: {{ $lh(12) }}; font-weight: normal; color: #fff; text-align: left; }
        table.items th.num { text-align: right; }
        table.items td { padding: 9px 12px; vertical-align: top; }
        table.items td.qty { white-space: nowrap; }
        table.items td.num { text-align: right; white-space: nowrap; }
        table.items .c-item { width: 55%; }
        table.items .c-qty { width: 11%; }
        table.items .c-rate { width: 17%; }
        table.items .c-amount { width: 17%; }
        .item-name { font-weight: bold; }
        .item-bullets { margin-top: 4px; }
        .item-bullets div { color: #737373; line-height: {{ $lh(14) }}; }

        /* Total menempel ke kanan, sejajar kolom Rate/Amount. */
        .totals { width: 62%; margin-left: auto; margin-top: 22px; }
        .totals td { padding: 4px 0; }
        .totals td.label { text-align: right; color: #737373; padding-right: 24px; }
        .totals td.num { width: 44%; text-align: right; white-space: nowrap; }
        .totals tr.grand td { padding-top: 6px; }
        .totals tr.due td { font-weight: bold; color: #171717; }

        /* Jarak-jarak di dokumen ini sengaja rapat: Notes dan Terms harus muat di
           halaman yang sama dengan tabel, sementara isi invoice bisa sampai 10+ poin. */
        .notes { margin-top: 20px; }
        .notes .label { color: #737373; margin-bottom: 3px; }
        .free-text { white-space: pre-line; line-height: {{ $lh(14.4) }}; }

        /* Terms menyambung langsung di bawah Notes, tidak pernah dipindah ke halaman
           baru. Jarak vertikal di seluruh dokumen ini dirapatkan supaya Notes dan
           Terms muat satu halaman bersama tabel -- lihat catatan di .notes. */
        .terms { margin-top: 14px; }
        .terms .label { color: #737373; margin-bottom: 6px; }
        .terms .block { margin-bottom: 10px; line-height: {{ $lh(14.4) }}; }
    </style>
</head>
<body>
    <table class="masthead">
        <tr>
            <td class="col-left">
                @if ($logo = \App\Support\CompanyProfile::logoData())
                    <img class="logo" src="{{ $logo }}" alt="">
                @endif

                <div class="company">
                    <div class="name">{{ $profile['name'] }}</div>
                    <div class="address">{{ $profile['address'] }}</div>
                </div>

                <div class="bill-to">
                    <div class="label">Bill To:</div>
                    <div class="name">{{ $bill['company_name'] ?? $invoice->client->company_name }}</div>
                </div>
            </td>

            <td class="col-right">
                <h1 class="doc-title">Invoice</h1>
                <div class="doc-number"># {{ $invoice->number }}</div>

                <table class="meta">
                    <tr>
                        <td class="label">Date:</td>
                        <td class="value">{{ $tanggal($invoice->issue_date) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Due Date:</td>
                        <td class="value">{{ $tanggal($invoice->due_date) }}</td>
                    </tr>
                    <tr class="balance">
                        <td class="label">Balance Due:</td>
                        <td class="value">{{ $money($invoice->balance_due) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="c-item">Item</th>
                <th class="c-qty num">Quantity</th>
                <th class="c-rate num">Rate</th>
                <th class="c-amount num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if ($bullets($item->description)->isNotEmpty())
                            <div class="item-bullets">
                                @foreach ($bullets($item->description) as $point)
                                    <div>- {{ $point }}</div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="qty">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }} {{ $item->unit }}</td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num">{{ $money($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="num">{{ $money($invoice->subtotal) }}</td>
        </tr>
        @if ((float) $invoice->discount_amount > 0)
            <tr>
                <td class="label">Discount:</td>
                <td class="num">-{{ $money($invoice->discount_amount) }}</td>
            </tr>
        @endif
        @if ((float) $invoice->tax_amount > 0)
            <tr>
                <td class="label">PPN {{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2, ',', '.'), '0'), ',') }}%:</td>
                <td class="num">{{ $money($invoice->tax_amount) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="label">Total:</td>
            <td class="num">{{ $money($invoice->total) }}</td>
        </tr>
        @if ((float) $invoice->amount_paid > 0)
            <tr>
                <td class="label">Sudah dibayar:</td>
                <td class="num">-{{ $money($invoice->amount_paid) }}</td>
            </tr>
            <tr class="due">
                <td class="label">Sisa Tagihan:</td>
                <td class="num">{{ $money($invoice->balance_due) }}</td>
            </tr>
        @endif
    </table>

    @if (filled($catatan))
        <div class="notes">
            <div class="label">Notes:</div>
            <div class="free-text">{{ $catatan }}</div>
        </div>
    @endif

    @if (filled($profile['terms']))
        <div class="terms">
            <div class="label">Terms:</div>
            <div class="block free-text">{{ $profile['terms'] }}</div>
        </div>
    @endif
</body>
</html>

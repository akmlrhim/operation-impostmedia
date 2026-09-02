@php
    /**
     * Dokumen yang dirender dari template blok sudah membawa <style> sendiri --
     * margin halaman, huruf, kop, dan watermark ikut dibekukan bersama isinya.
     * Gaya bawaan di bawah ini hanya untuk MoU lama yang masih memakai Blade
     * documents/mou.blade.php; kalau ikut dipasang, aturan `th, td { border }`
     * miliknya akan membingkai tabel tanda tangan dokumen baru.
     */
    $selfStyled = str_contains($body, 'class="doc-body"');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    @if ($printable ?? true)
        @fonts
    @endif

    <style>
        @if (! $selfStyled)
            @page { size: A4; margin: 20mm 18mm; }
            body { font-family: 'Geist', sans-serif; font-size: 11pt; line-height: 1.6; color: #111; }
            h1 { font-size: 14pt; text-align: center; text-transform: uppercase; margin-bottom: 4px; }
            h1 + p { text-align: center; margin-top: 0; }
            h2 { font-size: 11pt; margin-top: 18px; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; vertical-align: top; }
            tfoot td { font-weight: 600; }
            /* Blok tanda tangan tidak berbingkai. */
            table:last-of-type, table:last-of-type td { border: none; }
            table:last-of-type td { width: 50%; text-align: center; padding-top: 12px; }
        @else
            @page { size: A4; }
            body { margin: 0; }
        @endif

        .toolbar { margin-bottom: 16px; text-align: right; }
        .toolbar button { padding: 8px 16px; font-size: 10pt; cursor: pointer; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
@if ($printable ?? true)
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
@endif

    {!! $body !!}
</body>
</html>

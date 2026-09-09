@php
    $rupiah = fn ($n) => 'Rp'.number_format((float) $n, 0, ',', '.');
    $tanggal = fn ($d) => $d?->translatedFormat('d F Y') ?? '-';
@endphp

<h1>NOTA KESEPAHAMAN (MEMORANDUM OF UNDERSTANDING)</h1>
<p>Nomor: {{ $contract->number }}</p>

<p>
    Pada hari ini, {{ $tanggal($contract->signed_date) }}, bertempat di
    {{ $contract->signing_place ?: $profile['city'] }}, yang bertanda tangan di bawah ini:
</p>

<p>
    <strong>1. {{ $contract->first_party_name ?: $profile['signatory_name'] }}</strong><br>
    Jabatan: {{ $contract->first_party_position ?: $profile['signatory_position'] }}<br>
    Dalam hal ini bertindak untuk dan atas nama <strong>{{ $profile['name'] }}</strong>,
    berkedudukan di {{ $profile['address'] }},
    selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.
</p>

<p>
    <strong>2. {{ $contract->second_party_name ?: $client->contact_name }}</strong><br>
    Jabatan: {{ $contract->second_party_position ?: $client->contact_position }}<br>
    Dalam hal ini bertindak untuk dan atas nama <strong>{{ $client->company_name }}</strong>,
    berkedudukan di {{ $client->address }},
    selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
</p>

<p>
    PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama disebut PARA PIHAK, sepakat untuk
    mengikatkan diri dalam Nota Kesepahaman dengan ketentuan sebagai berikut:
</p>

<h2>Pasal 1 &mdash; Maksud dan Tujuan</h2>
<p>{{ $contract->title }}</p>

<h2>Pasal 2 &mdash; Ruang Lingkup Pekerjaan</h2>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Uraian Pekerjaan</th>
            <th>Volume</th>
            <th>Harga Satuan</th>
            <th>Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($contract->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->name }}@if ($item->description)<br><small>{{ $item->description }}</small>@endif</td>
                <td>{{ (int) $item->quantity }} {{ $item->unit }}</td>
                <td>{{ $rupiah($item->unit_price) }}</td>
                <td>{{ $rupiah($item->amount) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">Subtotal</td>
            <td>{{ $rupiah($contract->subtotal) }}</td>
        </tr>
        @if ((float) $contract->tax_amount > 0)
            <tr>
                <td colspan="4">PPN {{ (int) $contract->tax_percent }}%</td>
                <td>{{ $rupiah($contract->tax_amount) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="4"><strong>Nilai Pekerjaan</strong></td>
            <td><strong>{{ $rupiah($contract->value) }}</strong></td>
        </tr>
    </tfoot>
</table>

<h2>Pasal 3 &mdash; Jangka Waktu</h2>
<p>
    Nota Kesepahaman ini berlaku sejak {{ $tanggal($contract->start_date) }} sampai dengan
    {{ $tanggal($contract->end_date) }}, dan dapat diperpanjang atas kesepakatan PARA PIHAK.
</p>

<h2>Pasal 4 &mdash; Nilai dan Tata Cara Pembayaran</h2>
<p>Nilai pekerjaan adalah sebesar {{ $rupiah($contract->value) }}.</p>
<p>Pembayaran dilakukan sesuai invoice yang diterbitkan PIHAK PERTAMA.</p>

<h2>Pasal 5 &mdash; Hak dan Kewajiban</h2>
<p>
    PIHAK PERTAMA berkewajiban melaksanakan pekerjaan sesuai ruang lingkup pada Pasal 2 dan
    menyampaikan laporan berkala kepada PIHAK KEDUA. PIHAK KEDUA berkewajiban menyediakan data,
    akses, dan persetujuan yang diperlukan serta melakukan pembayaran sesuai Pasal 4.
</p>

<h2>Pasal 6 &mdash; Kerahasiaan</h2>
<p>
    PARA PIHAK sepakat menjaga kerahasiaan seluruh informasi yang diperoleh selama pelaksanaan
    Nota Kesepahaman ini.
</p>

<h2>Pasal 7 &mdash; Pengakhiran</h2>
<p>
    Nota Kesepahaman ini dapat diakhiri oleh salah satu pihak dengan pemberitahuan tertulis
    sekurang-kurangnya 30 (tiga puluh) hari sebelumnya.
</p>

<h2>Pasal 8 &mdash; Penyelesaian Perselisihan</h2>
<p>
    Perselisihan diselesaikan secara musyawarah untuk mufakat. Apabila tidak tercapai mufakat,
    PARA PIHAK sepakat menyelesaikannya melalui Pengadilan Negeri setempat.
</p>

<h2>Pasal 9 &mdash; Penutup</h2>
<p>
    Nota Kesepahaman ini dibuat dalam rangkap 2 (dua), bermeterai cukup, dan masing-masing
    mempunyai kekuatan hukum yang sama.
</p>

<table>
    <tr>
        <td>PIHAK PERTAMA<br>{{ $profile['name'] }}</td>
        <td>PIHAK KEDUA<br>{{ $client->company_name }}</td>
    </tr>
    <tr>
        <td>
            @if ($signature = $company->signatureData())
                <img src="{{ $signature }}" alt="" style="max-height: 64px; max-width: 180px;"><br>
            @else
                <br><br><br>
            @endif
            <u>{{ $contract->first_party_name ?: $profile['signatory_name'] }}</u><br>
            {{ $contract->first_party_position ?: $profile['signatory_position'] }}
        </td>
        <td>
            <br><br><br>
            <u>{{ $contract->second_party_name ?: $client->contact_name }}</u><br>
            {{ $contract->second_party_position ?: $client->contact_position }}
        </td>
    </tr>
</table>
